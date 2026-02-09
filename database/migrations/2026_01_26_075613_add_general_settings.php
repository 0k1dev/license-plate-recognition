<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', 'Bất động sản Admin');
        $this->migrator->add('general.site_logo', null);
        $this->migrator->add('general.auth_bg_image', null);
    }

    public function down(): void
    {
        $this->migrator->delete('general.site_name');
        $this->migrator->delete('general.site_logo');
        $this->migrator->delete('general.auth_bg_image');
    }
};
