<?php

// English (US) copy of the public site. Must contain exactly the same keys as lang/vi/messages.php;
// a missing key falls back to the Vietnamese copy. An empty string intentionally hides that element.
// Keep names, brand names (THK HOLDINGS VIETNAM, Year End Party), venues, dates, times and codes as they are.
return [
    'meta' => [
        'title'       => '{event} Invitation',
        'description' => '{weekday}, {date} · {time} · {venue}',
    ],

    'lang' => [
        'label' => 'Select language',
    ],

    // format: {d} day, {dd} 2-digit day, {mm} 2-digit month, {month} month name, {yyyy} year
    'date' => [
        'format'       => '{dd}/{mm}/{yyyy}',
        // Ordered as date('w'): 0 = Sunday
        'weekdays'     => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        'months'       => [
            1 => 'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ],
    ],

    'cover' => [
        'hint_swipe' => 'Swipe down to open',
        'hint_drag'  => 'Click and drag down to open',
        'hint_tap'   => 'Click or tap to open',
    ],

    'invite' => [
        'location_label' => '',
    ],

    'recap' => require __DIR__ . '/recap.php',

    'email_label'       => 'Email',
    'email_placeholder' => '@thk-hd.vn, @core-tech.jp',
    'thanks_declined'   => 'Thank you for your response. We’re sorry you won’t be able to join us this time.',

    'errors' => [
        'email_required' => 'Please enter your email address.',
        'email_invalid'  => 'Please enter a valid email address.',
    ],

    'rsvp' => [
        'title'             => 'Confirm Attendance',
        'lead'              => 'Kindly respond by {deadline} so we can make all the necessary arrangements.',
        'name_label'        => 'Full Name',
        'name_placeholder'  => 'Enter your full name',
        'attendance_label'  => 'Will you be joining us?',
        'attending'         => 'Yes, I will attend',
        'declined'          => 'Regretfully, I cannot attend',
        'hint'              => 'Once submitted, your response cannot be changed.',
        'submit'            => 'Confirm',
        'submitting'        => 'Submitting…',

        'thanks_attending'  => 'Thank you for confirming your attendance! We look forward to seeing you at {event}.',
        'status_label'      => 'Status',
        'status_attending'  => 'Attending',
        'recorded'          => '',
        'ticket_below'      => 'Your ticket is shown below.',
        'ticket_pending'    => '',
        'already_responded' => 'Your response has already been recorded and can no longer be changed.',

        'errors' => [
            'save_failed'         => 'We couldn’t save your response. Please try again in a few minutes.',
            'name_required'       => 'Please enter your full name.',
            'name_too_long'       => 'Your full name must be {max} characters or fewer.',
            'attendance_required' => 'Please let us know whether you can attend.',
            'email_domain'        => 'Please use your company email ({domains}).',
        ],
    ],

    'lookup' => [
        'title'             => 'Find Your Ticket',
        'lead'              => 'Already confirmed your attendance? Enter your registered email address to find your ticket.',
        'submit'            => 'Find Ticket',
        'submitting'        => 'Searching…',
        'found'             => 'We found your ticket! It’s shown below.',
        'not_found'         => 'No invitation or registration was found for this email address.',
        'not_responded'     => 'You haven’t submitted your RSVP yet.',
        'declined'          => 'You have confirmed that you cannot attend this event. No ticket is available.',
        'ticket_pending'    => 'Your ticket is currently being updated.',

        'errors' => [
            'failed'         => 'We couldn’t look up your ticket. Please try again in a few minutes.',
        ],
    ],

    'attendees' => [
        'label'       => 'Attendees',
        'title'       => 'Attendees',
        'count_label' => 'Confirmed',
        'empty'       => 'No confirmed attendees yet.',
    ],

    'map' => [
        'title' => 'Seating Plan',
    ],

    'program' => [
        'label'            => 'Program',
        'title'            => 'Event Schedule',
        'date_label'       => 'Save the Date',
        'dress_code'       => 'Party attire',
        'dress_code_label' => 'Dress Code',
        'dress_code_note'  => '',
        'palette_label'    => 'Dress code color palette',
        'items' => [
            'reception'    => 'Guest arrival & check-in',
            'opening'      => 'Opening ceremony & special program',
            'dinner'       => 'Dinner & networking',
            'performances' => 'Appreciation & recognition',
            'lucky_draw'   => 'Lucky draw',
            'farewell'     => 'Closing ceremony',
        ],
    ],

    'closing' => [
        'lead'  => 'We look forward to an unforgettable evening with you',
        'title' => 'Thank You!',
        'note'  => '',
    ],

    'invalid' => [
        'title'             => 'Invalid Link',
        'text'              => 'We’re sorry, but we couldn’t find a ticket for this link.',
        'unavailable_title' => 'Invitation Temporarily Unavailable',
        'unavailable_text'  => 'We couldn’t load your invitation. Please try again in a few minutes.',
        'back'              => 'Back to Invitation',
    ],

    'lightbox' => [
        'label' => 'Enlarged photo',
        'close' => 'Close',
    ],
];
