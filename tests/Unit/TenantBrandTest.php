<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Support\TenantBrand;
use PHPUnit\Framework\TestCase;

class TenantBrandTest extends TestCase
{
    public function test_invalid_stored_brand_colors_fall_back_to_safe_defaults(): void
    {
        $tenant = new Tenant([
            'name' => 'Safe Theme Vendor',
            'website_settings' => [
                'primary_color' => '#171513; background-image:url(https://attacker.invalid/x)',
                'secondary_color' => 'red',
                'accent_color' => '#AABBCC',
            ],
        ]);

        $brand = TenantBrand::for($tenant);

        $this->assertSame('#171513', $brand->primaryColor);
        $this->assertSame('#24211E', $brand->secondaryColor);
        $this->assertSame('#AABBCC', $brand->accentColor);
    }
}
