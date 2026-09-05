<?php
declare(strict_types=1);

namespace Core;

/**
 * The single source of truth for editable content.
 *
 * Every field on the public site is declared here once; the public templates
 * read the values and the dashboard builds its editor from the same
 * definitions. Adding a field here makes it editable everywhere.
 *
 * Field types: text, textarea, richtext, number, image, link, toggle, select,
 * icon, repeater (nestable one level).
 */
final class Schema
{
    public const ITALIC_HINT = 'Wrap a word in *asterisks* to set it in the accent italic. A new line becomes a line break.';

    /** Ordered list of section keys as they render on the home page. */
    public static function order(): array
    {
        return array_keys(self::sections());
    }

    public static function section(string $key): ?array
    {
        return self::sections()[$key] ?? null;
    }

    public static function fields(string $key): array
    {
        return self::sections()[$key]['fields'] ?? [];
    }

    public static function title(string $key): string
    {
        return self::sections()[$key]['title'] ?? ucfirst($key);
    }

    /** @return array<string, array{title:string, description:string, anchor:?string, fields:array}> */
    public static function sections(): array
    {
        return [
            // ------------------------------------------------------------------
            'header' => [
                'title'       => 'Header & navigation',
                'description' => 'The fixed bar at the top of the page.',
                'anchor'      => null,
                'always_on'   => true,
                'fields'      => [
                    ['key' => 'logo_image', 'type' => 'image', 'label' => 'Logo image', 'hint' => 'Optional. Leave empty to use the Eden Ridge monogram and wordmark.'],
                    ['key' => 'logo_text', 'type' => 'text', 'label' => 'Logo wordmark', 'max' => 24],
                    ['key' => 'logo_tagline', 'type' => 'text', 'label' => 'Logo tagline', 'max' => 30],
                    ['key' => 'sticky_on_scroll', 'type' => 'toggle', 'label' => 'Darken the bar on scroll', 'default' => true],
                    ['key' => 'nav_items', 'type' => 'repeater', 'label' => 'Navigation links', 'row_label' => 'label', 'fields' => [
                        ['key' => 'label', 'type' => 'text', 'label' => 'Label', 'max' => 20],
                        ['key' => 'anchor', 'type' => 'link', 'label' => 'Target', 'hint' => 'An anchor such as #about, or a full URL.'],
                        ['key' => 'is_visible', 'type' => 'toggle', 'label' => 'Visible', 'default' => true],
                    ]],
                    ['key' => 'cta_label', 'type' => 'text', 'label' => 'Button label', 'max' => 26],
                    ['key' => 'cta_target', 'type' => 'link', 'label' => 'Button target'],
                ],
            ],

            // ------------------------------------------------------------------
            'hero' => [
                'title'       => 'Hero',
                'description' => 'The full-height opening image, headline and the statistics strip beneath it.',
                'anchor'      => 'home',
                'always_on'   => true,
                'fields'      => [
                    ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow', 'max' => 48],
                    ['key' => 'headline', 'type' => 'textarea', 'label' => 'Headline', 'hint' => self::ITALIC_HINT, 'rows' => 3, 'max' => 90],
                    ['key' => 'body', 'type' => 'textarea', 'label' => 'Intro paragraph', 'rows' => 4, 'max' => 260],
                    ['key' => 'background_image', 'type' => 'image', 'label' => 'Background image'],
                    ['key' => 'primary_cta_label', 'type' => 'text', 'label' => 'Primary button label', 'max' => 28],
                    ['key' => 'primary_cta_target', 'type' => 'link', 'label' => 'Primary button target'],
                    ['key' => 'secondary_cta_label', 'type' => 'text', 'label' => 'Secondary button label', 'max' => 28],
                    ['key' => 'secondary_cta_target', 'type' => 'link', 'label' => 'Secondary button target'],
                    ['key' => 'scroll_label', 'type' => 'text', 'label' => 'Scroll cue label', 'max' => 12],
                    ['key' => 'show_stats', 'type' => 'toggle', 'label' => 'Show the statistics strip', 'default' => true],
                    ['key' => 'stats', 'type' => 'repeater', 'label' => 'Statistics', 'row_label' => 'value', 'max_rows' => 6, 'fields' => [
                        ['key' => 'value', 'type' => 'text', 'label' => 'Value', 'max' => 12],
                        ['key' => 'label', 'type' => 'text', 'label' => 'Label', 'max' => 22],
                    ]],
                ],
            ],

            // ------------------------------------------------------------------
            'vision' => [
                'title'       => 'Vision',
                'description' => 'The dark image-and-text spreads and the community banner below them.',
                'anchor'      => 'about',
                'fields'      => [
                    ['key' => 'spreads', 'type' => 'repeater', 'label' => 'Spreads', 'row_label' => 'eyebrow', 'fields' => [
                        ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow', 'max' => 40],
                        ['key' => 'headline', 'type' => 'textarea', 'label' => 'Headline', 'hint' => self::ITALIC_HINT, 'rows' => 2, 'max' => 70],
                        ['key' => 'body', 'type' => 'textarea', 'label' => 'Body', 'hint' => 'Leave a blank line between paragraphs.', 'rows' => 6],
                        ['key' => 'pull_quote', 'type' => 'text', 'label' => 'Pull quote', 'hint' => 'Optional. Shown with the brass rule beside it.'],
                        ['key' => 'image', 'type' => 'image', 'label' => 'Image'],
                        ['key' => 'caption', 'type' => 'text', 'label' => 'Image caption', 'hint' => 'Optional overlay caption, bottom-left of the image.', 'max' => 46],
                        ['key' => 'image_side', 'type' => 'select', 'label' => 'Image side', 'options' => ['left' => 'Image left', 'right' => 'Image right'], 'default' => 'left'],
                    ]],
                    ['key' => 'show_band', 'type' => 'toggle', 'label' => 'Show the community banner', 'default' => true],
                    ['key' => 'band_eyebrow', 'type' => 'text', 'label' => 'Banner eyebrow', 'max' => 40],
                    ['key' => 'band_headline', 'type' => 'textarea', 'label' => 'Banner headline', 'hint' => self::ITALIC_HINT, 'rows' => 2, 'max' => 80],
                    ['key' => 'band_body', 'type' => 'textarea', 'label' => 'Banner body', 'rows' => 4],
                    ['key' => 'band_image', 'type' => 'image', 'label' => 'Banner background image'],
                ],
            ],

            // ------------------------------------------------------------------
            'why' => [
                'title'       => 'Why Eden Ridge',
                'description' => 'The eight-card grid of selling points.',
                'anchor'      => 'why',
                'fields'      => [
                    ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow', 'max' => 40],
                    ['key' => 'headline', 'type' => 'textarea', 'label' => 'Headline', 'hint' => self::ITALIC_HINT, 'rows' => 2, 'max' => 60],
                    ['key' => 'lede', 'type' => 'textarea', 'label' => 'Intro paragraph', 'rows' => 3],
                    ['key' => 'items', 'type' => 'repeater', 'label' => 'Cards', 'row_label' => 'title', 'fields' => [
                        ['key' => 'icon', 'type' => 'icon', 'label' => 'Icon'],
                        ['key' => 'title', 'type' => 'text', 'label' => 'Title', 'max' => 44],
                        ['key' => 'body', 'type' => 'textarea', 'label' => 'Body', 'rows' => 3, 'max' => 190],
                    ]],
                ],
            ],

            // ------------------------------------------------------------------
            'residence' => [
                'title'       => 'The Residence',
                'description' => 'The dark specification card for the four-bedroom home.',
                'anchor'      => 'homes',
                'fields'      => [
                    ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow', 'max' => 40],
                    ['key' => 'headline', 'type' => 'textarea', 'label' => 'Headline', 'hint' => self::ITALIC_HINT, 'rows' => 2, 'max' => 60],
                    ['key' => 'tab_label', 'type' => 'text', 'label' => 'Model tab label', 'max' => 46],
                    ['key' => 'card_title', 'type' => 'text', 'label' => 'Card title', 'max' => 46],
                    ['key' => 'price_note', 'type' => 'text', 'label' => 'Price note', 'max' => 64],
                    ['key' => 'image', 'type' => 'image', 'label' => 'Residence image'],
                    ['key' => 'specs', 'type' => 'repeater', 'label' => 'Specifications', 'row_label' => 'label', 'fields' => [
                        ['key' => 'label', 'type' => 'text', 'label' => 'Label', 'max' => 30],
                        ['key' => 'value', 'type' => 'text', 'label' => 'Value', 'max' => 40],
                    ]],
                    ['key' => 'features', 'type' => 'repeater', 'label' => 'Feature list', 'row_label' => 'text', 'fields' => [
                        ['key' => 'text', 'type' => 'text', 'label' => 'Feature', 'max' => 52],
                    ]],
                    ['key' => 'cta_1_label', 'type' => 'text', 'label' => 'Left button label', 'max' => 28],
                    ['key' => 'cta_1_target', 'type' => 'link', 'label' => 'Left button target'],
                    ['key' => 'cta_2_label', 'type' => 'text', 'label' => 'Right button label', 'max' => 28],
                    ['key' => 'cta_2_target', 'type' => 'link', 'label' => 'Right button target'],
                ],
            ],

            // ------------------------------------------------------------------
            'residence_details' => [
                'title'       => 'Room detail spreads',
                'description' => 'The alternating room-by-room spreads: kitchen, dining, bathroom, bedroom, terrace and wardrobe.',
                'anchor'      => 'details',
                'fields'      => [
                    ['key' => 'blocks', 'type' => 'repeater', 'label' => 'Room blocks', 'row_label' => 'eyebrow', 'fields' => [
                        ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow', 'max' => 44],
                        ['key' => 'headline', 'type' => 'textarea', 'label' => 'Headline', 'hint' => self::ITALIC_HINT, 'rows' => 2, 'max' => 70],
                        ['key' => 'body', 'type' => 'textarea', 'label' => 'Body', 'rows' => 4],
                        ['key' => 'image', 'type' => 'image', 'label' => 'Image'],
                        ['key' => 'caption', 'type' => 'text', 'label' => 'Image caption', 'hint' => 'Optional, e.g. "03 — Designer Kitchen".', 'max' => 46],
                        ['key' => 'image_side', 'type' => 'select', 'label' => 'Image side', 'options' => ['left' => 'Image left', 'right' => 'Image right'], 'default' => 'left'],
                        ['key' => 'theme', 'type' => 'select', 'label' => 'Colour', 'options' => ['light' => 'Paper (light)', 'dark' => 'Pine (dark)'], 'default' => 'light'],
                    ]],
                ],
            ],

            // ------------------------------------------------------------------
            'gallery' => [
                'title'       => 'Gallery',
                'description' => 'Heading copy for the filtered render gallery. Images and categories are curated under Gallery in the sidebar.',
                'anchor'      => 'gallery',
                'fields'      => [
                    ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow', 'max' => 40],
                    ['key' => 'headline', 'type' => 'textarea', 'label' => 'Headline', 'hint' => self::ITALIC_HINT, 'rows' => 2, 'max' => 60],
                    ['key' => 'lede', 'type' => 'textarea', 'label' => 'Intro paragraph', 'rows' => 3],
                    ['key' => 'all_label', 'type' => 'text', 'label' => 'Label for the "all" filter', 'max' => 20],
                ],
            ],

            // ------------------------------------------------------------------
            'floorplans' => [
                'title'       => 'Floor plans',
                'description' => 'The tabbed floor plan panels.',
                'anchor'      => 'floorplans',
                'fields'      => [
                    ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow', 'max' => 40],
                    ['key' => 'headline', 'type' => 'textarea', 'label' => 'Headline', 'hint' => self::ITALIC_HINT, 'rows' => 2, 'max' => 60],
                    ['key' => 'lede', 'type' => 'textarea', 'label' => 'Intro paragraph', 'rows' => 3],
                    ['key' => 'plans', 'type' => 'repeater', 'label' => 'Plans', 'row_label' => 'tab_label', 'fields' => [
                        ['key' => 'tab_label', 'type' => 'text', 'label' => 'Tab label', 'max' => 34],
                        ['key' => 'title', 'type' => 'text', 'label' => 'Title', 'max' => 30],
                        ['key' => 'subtitle', 'type' => 'text', 'label' => 'Subtitle', 'max' => 30],
                        ['key' => 'image', 'type' => 'image', 'label' => 'Plan image'],
                        ['key' => 'rows', 'type' => 'repeater', 'label' => 'Schedule rows', 'row_label' => 'label', 'fields' => [
                            ['key' => 'label', 'type' => 'text', 'label' => 'Label', 'max' => 34],
                            ['key' => 'value', 'type' => 'text', 'label' => 'Value', 'max' => 28],
                            ['key' => 'is_total', 'type' => 'toggle', 'label' => 'Emphasise as a total row', 'default' => false],
                        ]],
                        ['key' => 'note', 'type' => 'textarea', 'label' => 'Note', 'rows' => 3],
                        ['key' => 'cta_text', 'type' => 'text', 'label' => 'Plan pack button text', 'max' => 46],
                    ]],
                ],
            ],

            // ------------------------------------------------------------------
            'amenities' => [
                'title'       => 'Community amenities',
                'description' => 'The numbered two-column amenity list.',
                'anchor'      => 'amenities',
                'fields'      => [
                    ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow', 'max' => 40],
                    ['key' => 'headline', 'type' => 'textarea', 'label' => 'Headline', 'hint' => self::ITALIC_HINT, 'rows' => 2, 'max' => 60],
                    ['key' => 'lede', 'type' => 'textarea', 'label' => 'Intro paragraph', 'rows' => 3],
                    ['key' => 'items', 'type' => 'repeater', 'label' => 'Amenities', 'row_label' => 'title', 'fields' => [
                        ['key' => 'number', 'type' => 'text', 'label' => 'Number', 'max' => 4],
                        ['key' => 'icon', 'type' => 'icon', 'label' => 'Icon', 'hint' => 'Optional — leave as “No icon” to match the printed layout.', 'default' => 'none'],
                        ['key' => 'title', 'type' => 'text', 'label' => 'Title', 'max' => 34],
                        ['key' => 'body', 'type' => 'textarea', 'label' => 'Body', 'rows' => 2, 'max' => 130],
                    ]],
                ],
            ],

            // ------------------------------------------------------------------
            'location' => [
                'title'       => 'Location',
                'description' => 'Distances, nearby amenities and the map panel.',
                'anchor'      => 'location',
                'fields'      => [
                    ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow', 'max' => 40],
                    ['key' => 'headline', 'type' => 'textarea', 'label' => 'Headline', 'hint' => self::ITALIC_HINT, 'rows' => 2, 'max' => 60],
                    ['key' => 'lede', 'type' => 'textarea', 'label' => 'Intro paragraph', 'rows' => 4],
                    ['key' => 'columns', 'type' => 'repeater', 'label' => 'Distance groups', 'row_label' => 'heading', 'fields' => [
                        ['key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'max' => 30],
                        ['key' => 'rows', 'type' => 'repeater', 'label' => 'Rows', 'row_label' => 'label', 'fields' => [
                            ['key' => 'label', 'type' => 'text', 'label' => 'Label', 'max' => 34],
                            ['key' => 'value', 'type' => 'text', 'label' => 'Value', 'max' => 26],
                        ]],
                    ]],
                    ['key' => 'map_type', 'type' => 'select', 'label' => 'Map style', 'options' => [
                        'stylised' => 'Stylised brass map (matches the site)',
                        'image'    => 'Static map image',
                        'iframe'   => 'Google Maps embed',
                        'none'     => 'No map',
                    ], 'default' => 'stylised'],
                    ['key' => 'map_pin_label', 'type' => 'text', 'label' => 'Map pin label', 'max' => 46],
                    ['key' => 'map_image', 'type' => 'image', 'label' => 'Static map image', 'hint' => 'Used when the map style is “Static map image”.'],
                    ['key' => 'map_embed_url', 'type' => 'text', 'label' => 'Google Maps embed URL', 'hint' => 'The src of a Google Maps embed iframe.'],
                    ['key' => 'map_link_label', 'type' => 'text', 'label' => 'Map link label', 'hint' => 'Optional link shown under the map.', 'max' => 30],
                    ['key' => 'map_link_url', 'type' => 'link', 'label' => 'Map link URL'],
                    ['key' => 'closing_line', 'type' => 'text', 'label' => 'Closing italic line', 'max' => 80],
                ],
            ],

            // ------------------------------------------------------------------
            'investment' => [
                'title'       => 'Investment case',
                'description' => 'The numbered investment list beside a render.',
                'anchor'      => 'investment',
                'fields'      => [
                    ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow', 'max' => 40],
                    ['key' => 'headline', 'type' => 'textarea', 'label' => 'Headline', 'hint' => self::ITALIC_HINT, 'rows' => 2, 'max' => 60],
                    ['key' => 'image', 'type' => 'image', 'label' => 'Supporting image'],
                    ['key' => 'items', 'type' => 'repeater', 'label' => 'Points', 'row_label' => 'title', 'fields' => [
                        ['key' => 'title', 'type' => 'text', 'label' => 'Title', 'max' => 44],
                        ['key' => 'body', 'type' => 'textarea', 'label' => 'Body', 'rows' => 3, 'max' => 220],
                    ]],
                    ['key' => 'summary', 'type' => 'text', 'label' => 'Closing italic line', 'max' => 80],
                ],
            ],

            // ------------------------------------------------------------------
            'process' => [
                'title'       => 'Payment structure',
                'description' => 'The four purchase stages and the pricing disclaimer.',
                'anchor'      => 'process',
                'fields'      => [
                    ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow', 'max' => 40],
                    ['key' => 'headline', 'type' => 'textarea', 'label' => 'Headline', 'hint' => self::ITALIC_HINT, 'rows' => 2, 'max' => 60],
                    ['key' => 'lede', 'type' => 'textarea', 'label' => 'Intro paragraph', 'rows' => 3],
                    ['key' => 'stages', 'type' => 'repeater', 'label' => 'Stages', 'row_label' => 'title', 'fields' => [
                        ['key' => 'stage_label', 'type' => 'text', 'label' => 'Stage label', 'max' => 16],
                        ['key' => 'title', 'type' => 'text', 'label' => 'Title', 'max' => 24],
                        ['key' => 'amount', 'type' => 'text', 'label' => 'Amount', 'max' => 14],
                        ['key' => 'body', 'type' => 'textarea', 'label' => 'Body', 'rows' => 3, 'max' => 200],
                    ]],
                    ['key' => 'disclaimer', 'type' => 'textarea', 'label' => 'Disclaimer', 'rows' => 3],
                ],
            ],

            // ------------------------------------------------------------------
            'faq' => [
                'title'       => 'FAQ',
                'description' => 'The accordion of common questions. Also emits FAQ structured data for Google.',
                'anchor'      => 'faq',
                'fields'      => [
                    ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow', 'max' => 40],
                    ['key' => 'headline', 'type' => 'textarea', 'label' => 'Headline', 'hint' => self::ITALIC_HINT, 'rows' => 2, 'max' => 60],
                    ['key' => 'first_open', 'type' => 'toggle', 'label' => 'Open the first question by default', 'default' => false],
                    ['key' => 'items', 'type' => 'repeater', 'label' => 'Questions', 'row_label' => 'question', 'fields' => [
                        ['key' => 'question', 'type' => 'text', 'label' => 'Question', 'max' => 110],
                        ['key' => 'answer', 'type' => 'richtext', 'label' => 'Answer'],
                    ]],
                ],
            ],

            // ------------------------------------------------------------------
            'experience' => [
                'title'       => 'Video walkthrough',
                'description' => 'The video tour banner. Settings for the video itself live under Video Tour in the sidebar.',
                'anchor'      => 'video',
                'fields'      => [
                    ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow', 'max' => 40],
                    ['key' => 'headline', 'type' => 'textarea', 'label' => 'Headline', 'hint' => self::ITALIC_HINT, 'rows' => 2, 'max' => 60],
                    ['key' => 'body', 'type' => 'textarea', 'label' => 'Body', 'rows' => 3, 'max' => 220],
                    ['key' => 'background_image', 'type' => 'image', 'label' => 'Background image'],
                    ['key' => 'cta_label', 'type' => 'text', 'label' => 'Button label', 'max' => 30],
                    ['key' => 'align', 'type' => 'select', 'label' => 'Text alignment', 'options' => ['right' => 'Right', 'left' => 'Left'], 'default' => 'right'],
                ],
            ],

            // ------------------------------------------------------------------
            'enquiry' => [
                'title'       => 'Enquiry form',
                'description' => 'Contact channels and the enquiry form. Submissions land in the Enquiries inbox.',
                'anchor'      => 'contact',
                'always_on'   => true,
                'fields'      => [
                    ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow', 'max' => 40],
                    ['key' => 'headline', 'type' => 'textarea', 'label' => 'Headline', 'hint' => self::ITALIC_HINT, 'rows' => 2, 'max' => 60],
                    ['key' => 'body', 'type' => 'textarea', 'label' => 'Body', 'rows' => 5],
                    ['key' => 'contact_items', 'type' => 'repeater', 'label' => 'Contact channels', 'row_label' => 'label', 'fields' => [
                        ['key' => 'type', 'type' => 'select', 'label' => 'Type', 'options' => [
                            'phone' => 'Phone', 'whatsapp' => 'WhatsApp', 'email' => 'Email', 'address' => 'Address',
                        ], 'default' => 'phone'],
                        ['key' => 'label', 'type' => 'text', 'label' => 'Label', 'max' => 24],
                        ['key' => 'value', 'type' => 'text', 'label' => 'Value', 'max' => 60],
                    ]],
                    ['key' => 'form_title', 'type' => 'text', 'label' => 'Form heading', 'max' => 44],
                    ['key' => 'name_label', 'type' => 'text', 'label' => 'Name field label', 'max' => 24],
                    ['key' => 'email_label', 'type' => 'text', 'label' => 'Email field label', 'max' => 24],
                    ['key' => 'phone_label', 'type' => 'text', 'label' => 'Phone field label', 'max' => 24],
                    ['key' => 'interest_label', 'type' => 'text', 'label' => 'Interest field label', 'max' => 24],
                    ['key' => 'message_label', 'type' => 'text', 'label' => 'Message field label', 'max' => 24],
                    ['key' => 'interest_placeholder', 'type' => 'text', 'label' => 'Interest placeholder', 'hint' => 'The unselected first entry in the dropdown. Leave empty to start on the first real option.', 'max' => 30],
                    ['key' => 'interest_options', 'type' => 'repeater', 'label' => 'Interest options', 'row_label' => 'label', 'fields' => [
                        ['key' => 'label', 'type' => 'text', 'label' => 'Option', 'max' => 46],
                    ]],
                    ['key' => 'submit_label', 'type' => 'text', 'label' => 'Submit button label', 'max' => 24],
                    ['key' => 'response_note', 'type' => 'text', 'label' => 'Note under the form', 'max' => 70],
                    ['key' => 'success_message', 'type' => 'textarea', 'label' => 'Thank-you message', 'rows' => 3],
                ],
            ],

            // ------------------------------------------------------------------
            'footer' => [
                'title'       => 'Footer',
                'description' => 'Link columns, legal copy and the floating WhatsApp button.',
                'anchor'      => null,
                'always_on'   => true,
                'fields'      => [
                    ['key' => 'logo_text', 'type' => 'text', 'label' => 'Wordmark', 'max' => 24],
                    ['key' => 'link_groups', 'type' => 'repeater', 'label' => 'Link columns', 'row_label' => 'heading', 'fields' => [
                        ['key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'max' => 22],
                        ['key' => 'links', 'type' => 'repeater', 'label' => 'Links', 'row_label' => 'label', 'fields' => [
                            ['key' => 'label', 'type' => 'text', 'label' => 'Label', 'max' => 34],
                            ['key' => 'target', 'type' => 'link', 'label' => 'Target'],
                        ]],
                    ]],
                    ['key' => 'show_socials', 'type' => 'toggle', 'label' => 'Show the social icon row', 'default' => false, 'hint' => 'Off by default — the reference site has no social row. Handles are set under Site Settings.'],
                    ['key' => 'copyright', 'type' => 'text', 'label' => 'Copyright line', 'hint' => 'Use {year} for the current year.', 'max' => 70],
                    ['key' => 'address_line', 'type' => 'text', 'label' => 'Address line', 'max' => 70],
                    ['key' => 'disclaimer', 'type' => 'textarea', 'label' => 'Disclaimer', 'rows' => 6],
                    ['key' => 'show_legal_links', 'type' => 'toggle', 'label' => 'Show privacy / terms links', 'default' => false],
                    ['key' => 'legal_links', 'type' => 'repeater', 'label' => 'Legal links', 'row_label' => 'label', 'fields' => [
                        ['key' => 'label', 'type' => 'text', 'label' => 'Label', 'max' => 24],
                        ['key' => 'target', 'type' => 'link', 'label' => 'Target'],
                    ]],
                    ['key' => 'show_whatsapp_float', 'type' => 'toggle', 'label' => 'Show the floating WhatsApp button', 'default' => true],
                ],
            ],
        ];
    }

    // ----------------------------------------------------------------------
    // Save-time coercion
    // ----------------------------------------------------------------------

    /**
     * Turn raw POST data into a clean, typed content document for a section.
     * Unknown keys are dropped, so a tampered form cannot inject fields.
     */
    public static function coerce(array $fields, array $input): array
    {
        $out = [];
        foreach ($fields as $field) {
            $key = $field['key'];
            $raw = $input[$key] ?? null;
            $out[$key] = self::coerceField($field, $raw);
        }
        return $out;
    }

    private static function coerceField(array $field, mixed $raw): mixed
    {
        switch ($field['type']) {
            case 'repeater':
                $rows = [];
                if (is_array($raw)) {
                    foreach ($raw as $row) {
                        if (!is_array($row)) {
                            continue;
                        }
                        if (($row['_deleted'] ?? '') === '1') {
                            continue;
                        }
                        $clean = self::coerce($field['fields'], $row);
                        if (self::isEmptyRow($clean)) {
                            continue; // An untouched row would render as a blank card.
                        }
                        $rows[] = $clean;
                    }
                }
                if (isset($field['max_rows'])) {
                    $rows = array_slice($rows, 0, (int)$field['max_rows']);
                }
                return $rows;

            case 'toggle':
                return (bool)$raw;

            case 'number':
                return $raw === null || $raw === '' ? null : (float)$raw;

            case 'image':
                return $raw === null || $raw === '' ? null : (int)$raw;

            case 'richtext':
                return Sanitizer::richtext(is_string($raw) ? $raw : '');

            case 'select':
                $options = $field['options'] ?? [];
                $value   = is_string($raw) ? $raw : '';
                return array_key_exists($value, $options) ? $value : (string)($field['default'] ?? array_key_first($options));

            case 'icon':
                $value = is_string($raw) ? $raw : '';
                return Icons::has($value) ? $value : (string)($field['default'] ?? 'none');

            case 'link':
                $value = Sanitizer::text(is_string($raw) ? $raw : '');
                return self::safeLink($value);

            case 'textarea':
                $value = is_string($raw) ? $raw : '';
                return trim(str_replace("\r\n", "\n", strip_tags($value)));

            case 'text':
            default:
                return Sanitizer::text(is_string($raw) ? $raw : '');
        }
    }

    /** True when every value in a repeater row is blank. */
    private static function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (is_array($value)) {
                if ($value !== [] && !self::isEmptyRow($value)) {
                    return false;
                }
                continue;
            }
            if (is_bool($value)) {
                continue; // A toggle alone does not make a row meaningful.
            }
            if ($value !== '' && $value !== null && $value !== 'none') {
                return false;
            }
        }
        return true;
    }

    /** Allow anchors, relative paths, http(s), mailto and tel only. */
    public static function safeLink(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (preg_match('#^(https?://|mailto:|tel:|/|\#)#i', $url)) {
            return $url;
        }
        // Anything carrying its own scheme (javascript:, data:, …) is dropped.
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
            return '';
        }
        // A bare host becomes an absolute URL; anything else is an anchor.
        if (preg_match('#^[a-z0-9-]+(\.[a-z0-9-]+)+(/|$)#i', $url)) {
            return 'https://' . $url;
        }
        return '#' . ltrim($url, '#');
    }

    /** Blank content document for a section, honouring field defaults. */
    public static function blank(array $fields): array
    {
        $out = [];
        foreach ($fields as $field) {
            $out[$field['key']] = match ($field['type']) {
                'repeater' => [],
                'toggle'   => (bool)($field['default'] ?? false),
                'image', 'number' => null,
                'select'   => (string)($field['default'] ?? array_key_first($field['options'] ?? ['' => ''])),
                'icon'     => (string)($field['default'] ?? 'none'),
                default    => '',
            };
        }
        return $out;
    }
}
