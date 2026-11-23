<?php

return [
    'errors' => [
        'amount_below_min' => 'The minimum withdrawal is :min SAR.',
        'amount_above_max' => 'The maximum withdrawal is :max SAR.',
        'insufficient_balance' => 'This amount is more than your available balance.',
        'open_request_exists' => 'You already have a withdrawal in progress.',
        'bank_account_missing' => 'Add a bank account to withdraw your earnings.',
        'bank_account_unverified' => 'Your bank account is still being verified.',
        'withdrawals_disabled' => 'Withdrawals are currently unavailable.',
        'negative_balance' => 'Your balance is negative. It will recover from future earnings.',
        'cannot_cancel' => 'This withdrawal can no longer be cancelled.',
        'invalid_iban' => 'Enter a valid Saudi IBAN.',
        'invalid_mobile' => 'Enter a valid Saudi mobile number.',
        'owner_only' => 'Only the cafe owner can access the wallet.',
        'admin_only' => 'You do not have permission to do this.',
        'booking_not_paid' => 'Booking not paid',
        'booking_expired' => 'This booking expired before it was paid. Please book again.',
        'invalid_transition' => 'This action is not allowed for the request\'s current status.',
        'bank_snapshot_mismatch' => 'The bank account no longer matches a verified account.',
        'payout_mode_manual' => 'Payouts are set to manual transfer.',
        'payout_not_configured' => 'Automatic Moyasar payouts are not enabled yet.',
        'payout_status_unknown' => 'Could not confirm the payout status with Moyasar. Try again shortly.',
        'payout_in_flight' => 'A Moyasar payout for this request is still active.',
    ],
    'settings' => [
        'percent_range' => 'A percentage must be between 0 and 100.',
        'max_gte_min' => 'The maximum must be greater than or equal to the minimum.',
        'min_gt_fee' => 'The minimum withdrawal must be greater than the withdrawal fee.',
    ],
];
