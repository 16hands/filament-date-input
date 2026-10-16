<?php

return [

    'levels' => [
        'decade' => 'Decade',
        'year' => 'Year',
        'month' => 'Month',
        'day' => 'Day',
    ],

    'level_plurals' => [
        'decade' => 'decades',
        'year' => 'years',
        'month' => 'months',
        'day' => 'days',
    ],

    'messages' => [
        'format' => 'Enter the date as :pattern, e.g. :example',
        'full_year' => 'Please enter the full year, e.g. :year',
        'no_such_date' => "That date doesn't exist",
        'after_max' => 'Must be on or before :date',
        'in_future' => "Can't be in the future",
        'before_min' => 'Must be on or after :date',
    ],

    'calendar' => [
        'open' => 'Open calendar for :label',
        'choose' => 'Choose :label',
        'previous' => 'Previous',
        'next' => 'Next',
        'level' => 'Calendar level',
        'today' => 'Today',
        'clear' => 'Clear',
        'months' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        'months_short' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        'weekdays_short' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
    ],

    'help' => [
        'trigger' => 'How to enter dates for :label',
        'link' => 'How to enter dates',
        'input_hint' => 'Press ? for help',
        'title' => 'Entering dates',
        'type_heading' => 'Type it',
        'single_digits' => 'single digits are fine',
        'other_separators' => 'dashes, dots or spaces work',
        'full_year' => 'Always the full year: :year, not :short_year',
        'finish' => 'Enter or Tab to finish',
        'open_help' => 'opens this help',
        'pick_heading' => 'Or pick it',
        'pick_levels' => 'Calendar: :levels',
        'pick_up' => 'Click the heading to go back up a level',
        'keys_heading' => 'Keys in the calendar',
        'keys' => [
            'Alt+↓' => 'open',
            '← ↑ → ↓' => 'move',
            'Enter' => 'pick',
            'Backspace' => 'up a level',
            'PgUp / PgDn' => 'page',
            'Esc' => 'close',
        ],
        'field_note' => 'This field: :notes.',
        'note_in_future' => "can't be in the future",
        'note_on_or_before' => 'on or before :date',
        'note_on_or_after' => 'on or after :date',
        'note_opens_at' => 'the calendar opens at :level',
    ],

];
