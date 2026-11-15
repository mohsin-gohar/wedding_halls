<?php

return [

    // --------------------------------------------------------------------
    // SaaS billing surfaces (plan pricing cards, subscription approvals).
    // Disabled during the launch promotion: hall owners use the entire
    // platform free for the first month so the marketplace fills with
    // halls before paid plans are introduced again.
    // --------------------------------------------------------------------
    'billing_enabled' => env('BILLING_ENABLED', false),

    // --------------------------------------------------------------------
    // Kitchen / KOT module (Chef KOT board, kitchen KPI card, KOT doc
    // links). Wedding-hall onboarding does not require the catering
    // engine on day one — flip the env toggle to surface it again.
    // --------------------------------------------------------------------
    'kitchen_module_enabled' => env('KITCHEN_MODULE_ENABLED', false),

    // --------------------------------------------------------------------
    // Complimentary access window granted to every newly onboarded hall
    // (self-registered or provisioned by the platform).
    // --------------------------------------------------------------------
    'free_access_days' => (int) env('FREE_ACCESS_DAYS', 30),

    // --------------------------------------------------------------------
    // Default state for vendor self-registration when no administrative
    // override has been cached by the Super Admin console.
    // --------------------------------------------------------------------
    'vendor_registration_open' => env('VENDOR_REGISTRATION_OPEN', true),

    // --------------------------------------------------------------------
    // White-label vendor websites.
    //
    // Each vendor may publish its own branded microsite on a subdomain of
    // the platform root domain (e.g. nawabcaterers.riwajgrand.pk/site).
    // Set WHITE_LABEL_ENABLED=false to serve the standard platform branding
    // everywhere.
    // --------------------------------------------------------------------
    'white_label_enabled' => env('WHITE_LABEL_ENABLED', true),

    'root_domain' => env('ROOT_DOMAIN', 'riwajgrand.pk'),

];
