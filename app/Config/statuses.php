<?php
declare(strict_types=1);

/**
 * app/Config/statuses.php
 * ---------------------------------------------------------------------
 * The single source of truth for the repair job state machine.
 *
 * app/Services/JobService.php MUST check every status change against
 * `transitions` before writing to the database — never update
 * repair_jobs.status directly from a controller. This is what stops a
 * job going from 'collected' back to 'in_repair' by a stray form
 * resubmit, or a kiosk POST skipping straight to 'ready_for_collection'.
 *
 * The keys here are exactly the values of the repair_jobs.status ENUM
 * in database/schema.sql. If you add a status, add it in BOTH places —
 * there is intentionally no code that derives one from the other,
 * because a silent drift between the DB ENUM and this file is worse
 * than a duplicated list that fails loudly when they disagree.
 * ---------------------------------------------------------------------
 */

return [

    // -------------------------------------------------------------
    // Status metadata
    // -------------------------------------------------------------
    // label       — shown to staff
    // public_label — shown to the borrower on the track page (can be
    //                softer/vaguer wording than the internal label)
    // color       — badge color key, resolved by status-badge.php
    // is_open     — true while the job occupies the equipment
    //               (mirrors the active_equipment_lock generated column
    //               logic in schema.sql: everything except collected
    //               and cancelled is "open")
    // is_terminal — no further transitions possible
    // -------------------------------------------------------------
    'meta' => [
        'awaiting_assessment' => [
            'label'        => 'Awaiting Assessment',
            'public_label' => 'Received — waiting to be looked at',
            'color'        => 'gray',
            'is_open'      => true,
            'is_terminal'  => false,
        ],
        'assessing' => [
            'label'        => 'Assessing',
            'public_label' => 'Being diagnosed',
            'color'        => 'blue',
            'is_open'      => true,
            'is_terminal'  => false,
        ],
        'awaiting_parts' => [
            'label'        => 'Awaiting Parts',
            'public_label' => 'Waiting on a replacement part',
            'color'        => 'amber',
            'is_open'      => true,
            'is_terminal'  => false,
        ],
        'in_repair' => [
            'label'        => 'In Repair',
            'public_label' => 'Repair in progress',
            'color'        => 'blue',
            'is_open'      => true,
            'is_terminal'  => false,
        ],
        'ready_for_collection' => [
            'label'        => 'Ready for Collection',
            'public_label' => 'Ready — please come collect it',
            'color'        => 'green',
            'is_open'      => true,
            'is_terminal'  => false,
        ],
        'collected' => [
            'label'        => 'Collected',
            'public_label' => 'Collected',
            'color'        => 'green',
            'is_open'      => false,
            'is_terminal'  => true,
        ],
        'unrepairable' => [
            'label'        => 'Unrepairable',
            'public_label' => 'Could not be repaired — please collect',
            'color'        => 'red',
            'is_open'      => true,   // still sitting at the counter, must be collected
            'is_terminal'  => false,
        ],
        'cancelled' => [
            'label'        => 'Cancelled',
            'public_label' => 'Cancelled',
            'color'        => 'red',
            'is_open'      => false,
            'is_terminal'  => true,
        ],
    ],

    // -------------------------------------------------------------
    // Allowed transitions
    // -------------------------------------------------------------
    // from_status => [ list of to_status it may move to ]
    // A status with an empty array is terminal — enforced here AND
    // by is_terminal above, deliberately redundant so a typo in one
    // doesn't silently open a hole in the other.
    // -------------------------------------------------------------
    'transitions' => [
        'awaiting_assessment' => ['assessing', 'cancelled'],
        'assessing'           => ['awaiting_parts', 'in_repair', 'unrepairable', 'cancelled'],
        'awaiting_parts'      => ['in_repair', 'unrepairable', 'cancelled'],
        'in_repair'           => ['ready_for_collection', 'awaiting_parts', 'unrepairable'],
        'ready_for_collection' => ['collected', 'in_repair'],   // 'in_repair' covers a fault found again at pickup
        'unrepairable'        => ['collected', 'cancelled'],
        'collected'           => [],
        'cancelled'           => [],
    ],

    // -------------------------------------------------------------
    // Which transitions the KIOSK (unauthenticated) is allowed to make.
    // Everything else requires a logged-in staff session. This is the
    // list JobService checks when $source === 'kiosk'; it is
    // deliberately narrow — the kiosk creates jobs and closes them,
    // nothing in between.
    // -------------------------------------------------------------
    'kiosk_allowed_transitions' => [
        'ready_for_collection' => ['collected'],
        'unrepairable'         => ['collected'],
    ],

    // Status a brand-new job is created with. Referenced by
    // JobService::createJob() instead of a hardcoded string.
    'initial_status' => 'awaiting_assessment',

    // Statuses that block a NEW drop-off of the same asset in the
    // kiosk UI (pre-check before the DB unique-constraint catches it).
    // Equal to every status with is_open = true above; listed
    // explicitly so this array can be used without looping meta[].
    'blocking_statuses' => [
        'awaiting_assessment',
        'assessing',
        'awaiting_parts',
        'in_repair',
        'ready_for_collection',
        'unrepairable',
    ],
];
