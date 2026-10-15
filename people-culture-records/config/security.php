<?php

return [
    'storage_encrypted' => (bool) env('SECURITY_STORAGE_ENCRYPTED', false),
    'backups_encrypted' => (bool) env('SECURITY_BACKUPS_ENCRYPTED', false),
    'data_residency_confirmed' => (bool) env('SECURITY_DATA_RESIDENCY_ZAMBIA', false),
    'privacy_governance_approved' => (bool) env('SECURITY_PRIVACY_GOVERNANCE_APPROVED', false),
];
