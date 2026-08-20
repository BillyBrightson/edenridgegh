/* Eden Ridge dashboard — repeaters, media picker, dirty-state guard. */
(function () {
  'use strict';

  var csrf = document.querySelector('meta[name="csrf"]');
  var CSRF = csrf ? csrf.content : '';

  /* ------------------------------------------------------ sidebar (mobile) */
  var toggle = document.querySelector('.menu-toggle');
  var sidebar = document.querySelector('.sidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
      var scrim = document.querySelector('.scrim');
      if (sidebar.classList.contains('open')) {
        scrim = document.createElement('div');
        scrim.className = 'scrim';
        scrim.addEventListener('click', function () {
          sidebar.classList.remove('open');
          scrim.remove();
        });
        document.body.appendChild(scrim);
      } else if (scrim) {
        scrim.remove();
      }
    });
  }

  /* ------------------------------------------------------------ dirty guard */
  var form = document.querySelector('form[data-guard]');
  var dirty = false;
  function markDirty() {
    if (dirty) return;
    dirty = true;
    document.querySelectorAll('[data-dirty-state]').forEach(function (el) {
      el.textContent = 'Unsaved changes';
      el.classList.add('dirty');
    });
  }
  if (form) {
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);
    form.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) {
      if (!dirty) return;
      e.preventDefault();
      e.returnValue = '';
    });
  }
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.dataset.confirm)) e.preventDefault();
    });
  });

  /* ---------------------------------------------------------- char counters */
  function bindCounter(input) {
    var max = parseInt(input.dataset.max, 10);
    if (!max) return;
    var counter = document.createElement('span');
    counter.className = 'counter';
    var label = input.closest('.field') ? input.closest('.field').querySelector('.field-label, label') : null;
    if (!label) return;
    label.appendChild(counter);
    var update = function () {
      counter.textContent = input.value.length + ' / ' + max;
      counter.classList.toggle('over', input.value.length > max);
    };
    input.addEventListener('input', update);
    update();
  }
  document.querySelectorAll('[data-max]').forEach(bindCounter);

  /* --------------------------------------------------------------- repeaters */
  var counter = 0;

  function rowTitle(row) {
    var source = row.querySelector('[data-row-title]');
    var title = row.querySelector('.rep-head .title');
    if (!title) return;
    var value = source ? (source.value || '').trim() : '';
    title.textContent = value !== '' ? value.replace(/\*/g, '') : (row.dataset.emptyLabel || 'Untitled');
  }

  function renumber(repeater) {
    var rows = repeater.querySelectorAll(':scope > .rep-rows > .rep-row');
    Array.prototype.forEach.call(rows, function (row, index) {
      var num = row.querySelector('.rep-head .num');
      if (num) num.textContent = index + 1;
    });
  }

  function reindex(repeater) {
    // Names carry the DOM order so a drag-reorder survives the save.
    var base = repeater.dataset.name;
    var depth = repeater.dataset.depth;
    var rows = repeater.querySelectorAll(':scope > .rep-rows > .rep-row');
    Array.prototype.forEach.call(rows, function (row, index) {
      row.querySelectorAll('[name]').forEach(function (input) {
        var name = input.getAttribute('name');
        if (name.indexOf(base + '[') !== 0) return;
        var rest = name.slice(base.length);
        input.setAttribute('name', base + rest.replace(/^\[[^\]]*\]/, '[' + index + ']'));
      });
      row.dataset.index = index;
    });
    void depth;
  }

  function bindRow(row) {
    var head = row.querySelector('.rep-head');
    if (head) {
      head.addEventListener('click', function (e) {
        if (e.target.closest('.icon-btn') || e.target.closest('.drag')) return;
        row.classList.toggle('collapsed');
      });
    }
    var titleSource = row.querySelector('[data-row-title]');
    if (titleSource) {
      titleSource.addEventListener('input', function () { rowTitle(row); });
    }
    rowTitle(row);

    row.querySelectorAll(':scope > .rep-head .rep-actions [data-action]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var repeater = row.closest('.rep');
        var action = btn.dataset.action;
        if (action === 'delete') {
          if (!window.confirm('Remove this item?')) return;
          row.remove();
        } else if (action === 'duplicate') {
          var clone = row.cloneNode(true);
          row.after(clone);
          initRow(clone);
        } else if (action === 'up' && row.previousElementSibling) {
          row.previousElementSibling.before(row);
        } else if (action === 'down' && row.nextElementSibling) {
          row.nextElementSibling.after(row);
        }
        reindex(repeater);
        renumber(repeater);
        markDirty();
      });
    });

    // Drag to reorder.
    var handle = row.querySelector(':scope > .rep-head .drag');
    if (handle) {
      handle.addEventListener('mousedown', function () { row.draggable = true; });
      handle.addEventListener('mouseup', function () { row.draggable = false; });
      row.addEventListener('dragstart', function (e) {
        row.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', '');
      });
      row.addEventListener('dragend', function () {
        row.classList.remove('dragging');
        row.draggable = false;
        var repeater = row.closest('.rep');
        reindex(repeater);
        renumber(repeater);
        markDirty();
      });
    }
  }

  function initRow(row) {
    bindRow(row);
    row.querySelectorAll('[data-max]').forEach(bindCounter);
    row.querySelectorAll('.rep').forEach(initRepeater);
    row.querySelectorAll('[data-media-field]').forEach(initMediaField);
    row.querySelectorAll('[data-icon-field]').forEach(initIconField);
    row.querySelectorAll('[data-richtext]').forEach(initRichtext);
  }

  function initRepeater(repeater) {
    if (repeater.dataset.ready === '1') return;
    repeater.dataset.ready = '1';
    var rowsBox = repeater.querySelector(':scope > .rep-rows');
    repeater.querySelectorAll(':scope > .rep-rows > .rep-row').forEach(bindRow);
    renumber(repeater);

    rowsBox.addEventListener('dragover', function (e) {
      e.preventDefault();
      var dragging = rowsBox.querySelector('.rep-row.dragging');
      if (!dragging) return;
      var after = null;
      rowsBox.querySelectorAll('.rep-row:not(.dragging)').forEach(function (sibling) {
        var box = sibling.getBoundingClientRect();
        if (e.clientY > box.top + box.height / 2) after = sibling;
      });
      if (after) after.after(dragging);
      else rowsBox.prepend(dragging);
    });

    var addBtn = repeater.querySelector(':scope > .rep-add');
    var tpl = repeater.querySelector(':scope > template');
    if (addBtn && tpl) {
      addBtn.addEventListener('click', function (e) {
        e.preventDefault();
        counter += 1;
        var token = repeater.dataset.token;
        var html = tpl.innerHTML.split(token).join('n' + counter);
        var wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        var row = wrapper.firstElementChild;
        rowsBox.appendChild(row);
        initRow(row);
        reindex(repeater);
        renumber(repeater);
        markDirty();
        row.classList.remove('collapsed');
        var firstInput = row.querySelector('input, textarea, select');
        if (firstInput) firstInput.focus();
      });
    }
  }

  document.querySelectorAll('.rep').forEach(initRepeater);

  /* ------------------------------------------------------------ media field */
  var picker = document.getElementById('mediaPicker');
  var pickerBody = document.getElementById('mediaPickerBody');
  var pickerTarget = null;
  var pickerSelection = null;

  function openPicker(target) {
    pickerTarget = target;
    pickerSelection = null;
    picker.classList.add('open');
    loadPicker('');
  }

  function closePicker() {
    picker.classList.remove('open');
    pickerTarget = null;
  }

  function loadPicker(search) {
    pickerBody.innerHTML = '<p class="empty">Loading…</p>';
    fetch('/media/picker?q=' + encodeURIComponent(search), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.text(); })
      .then(function (html) {
        pickerBody.innerHTML = html;
        pickerBody.querySelectorAll('.media-tile').forEach(function (tile) {
          tile.addEventListener('click', function () {
            pickerBody.querySelectorAll('.media-tile').forEach(function (t) { t.classList.remove('selected'); });
            tile.classList.add('selected');
            pickerSelection = {
              id: tile.dataset.id,
              thumb: tile.dataset.thumb,
              name: tile.dataset.name,
              alt: tile.dataset.alt
            };
          });
          tile.addEventListener('dblclick', function () { applySelection(); });
        });
      });
  }

  function applySelection() {
    if (!pickerTarget || !pickerSelection) return closePicker();
    setMedia(pickerTarget, pickerSelection);
    closePicker();
    markDirty();
  }

  function setMedia(field, media) {
    var input = field.querySelector('input[type=hidden]');
    var preview = field.querySelector('.media-preview');
    var name = field.querySelector('.media-meta .name');
    var alt = field.querySelector('.media-meta .alt');
    input.value = media.id || '';
    if (media.id) {
      preview.innerHTML = '<img src="' + media.thumb + '" alt="">';
      if (name) name.textContent = media.name || '';
      if (alt) alt.textContent = media.alt || '';
      field.querySelector('[data-media-clear]').hidden = false;
    } else {
      preview.innerHTML = 'No image';
      if (name) name.textContent = 'No image selected';
      if (alt) alt.textContent = '';
      field.querySelector('[data-media-clear]').hidden = true;
    }
  }

  function initMediaField(field) {
    if (field.dataset.ready === '1') return;
    field.dataset.ready = '1';
    field.querySelector('[data-media-choose]').addEventListener('click', function (e) {
      e.preventDefault();
      openPicker(field);
    });
    var clear = field.querySelector('[data-media-clear]');
    clear.addEventListener('click', function (e) {
      e.preventDefault();
      setMedia(field, { id: '' });
      markDirty();
    });
    var upload = field.querySelector('input[type=file]');
    if (upload) {
      upload.addEventListener('change', function () {
        if (!upload.files.length) return;
        var body = new FormData();
        body.append('file', upload.files[0]);
        body.append('_csrf', CSRF);
        field.querySelector('.media-preview').textContent = 'Uploading…';
        fetch('/media/upload', {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: body
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data.ok) {
              setMedia(field, data.media);
              markDirty();
            } else {
              window.alert(data.error || 'The upload failed.');
              setMedia(field, { id: field.querySelector('input[type=hidden]').value });
            }
            upload.value = '';
          });
      });
    }
  }
  document.querySelectorAll('[data-media-field]').forEach(initMediaField);

  if (picker) {
    picker.querySelectorAll('[data-picker-close]').forEach(function (btn) {
      btn.addEventListener('click', closePicker);
    });
    var applyBtn = document.getElementById('mediaPickerApply');
    if (applyBtn) applyBtn.addEventListener('click', applySelection);
    var searchBox = document.getElementById('mediaPickerSearch');
    if (searchBox) {
      var timer = null;
      searchBox.addEventListener('input', function () {
        window.clearTimeout(timer);
        timer = window.setTimeout(function () { loadPicker(searchBox.value); }, 250);
      });
    }
    picker.addEventListener('click', function (e) { if (e.target === picker) closePicker(); });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && picker.classList.contains('open')) closePicker();
    });
  }

  /* ------------------------------------------------------------- icon field */
  function initIconField(field) {
    if (field.dataset.ready === '1') return;
    field.dataset.ready = '1';
    field.querySelectorAll('.icon-choice').forEach(function (choice) {
      choice.addEventListener('click', function () {
        field.querySelectorAll('.icon-choice').forEach(function (c) { c.classList.remove('selected'); });
        choice.classList.add('selected');
        field.querySelector('input[type=hidden]').value = choice.dataset.icon;
        markDirty();
      });
    });
  }
  document.querySelectorAll('[data-icon-field]').forEach(initIconField);

  /* --------------------------------------------------------------- richtext */
  function initRichtext(wrapper) {
    if (wrapper.dataset.ready === '1') return;
    wrapper.dataset.ready = '1';
    var area = wrapper.querySelector('.rt-area');
    var input = wrapper.querySelector('textarea');
    area.innerHTML = input.value;
    area.addEventListener('input', function () {
      input.value = area.innerHTML;
      markDirty();
    });
    wrapper.querySelectorAll('.rt-toolbar button').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var command = btn.dataset.cmd;
        if (command === 'createLink') {
          var url = window.prompt('Link address (https://…, mailto:… or #anchor)');
          if (!url) return;
          document.execCommand('createLink', false, url);
        } else {
          document.execCommand(command, false, null);
        }
        area.focus();
        input.value = area.innerHTML;
        markDirty();
      });
    });
  }
  document.querySelectorAll('[data-richtext]').forEach(initRichtext);

  /* ------------------------------------------------- section list reordering */
  var sectionList = document.querySelector('[data-section-order]');
  if (sectionList) {
    var listBox = sectionList.querySelector('ul');
    listBox.querySelectorAll('li').forEach(function (li) {
      var handle = li.querySelector('.drag');
      if (!handle) return;
      handle.addEventListener('mousedown', function () { li.draggable = true; });
      handle.addEventListener('mouseup', function () { li.draggable = false; });
      li.addEventListener('dragstart', function (e) {
        li.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', '');
      });
      li.addEventListener('dragend', function () {
        li.classList.remove('dragging');
        li.draggable = false;
        var ids = Array.prototype.map.call(listBox.querySelectorAll('li'), function (row) {
          return row.dataset.id;
        });
        fetch('/pages/home/reorder', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: 'order=' + ids.join(',') + '&_csrf=' + encodeURIComponent(CSRF)
        });
      });
    });
    listBox.addEventListener('dragover', function (e) {
      e.preventDefault();
      var dragging = listBox.querySelector('li.dragging');
      if (!dragging) return;
      var after = null;
      listBox.querySelectorAll('li:not(.dragging)').forEach(function (sibling) {
        var box = sibling.getBoundingClientRect();
        if (e.clientY > box.top + box.height / 2) after = sibling;
      });
      if (after) after.after(dragging);
      else listBox.prepend(dragging);
    });
  }

  /* ------------------------------------------------------ close account menu */
  document.addEventListener('click', function (e) {
    document.querySelectorAll('details.account[open]').forEach(function (d) {
      if (!d.contains(e.target)) d.removeAttribute('open');
    });
  });
})();
