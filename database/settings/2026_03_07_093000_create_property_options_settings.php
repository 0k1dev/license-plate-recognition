<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('property_options.amenities', config('property.amenities', []));
        $this->migrator->add('property_options.directions', config('property.directions', []));
        $this->migrator->add('property_options.shapes', config('property.shapes', []));
        $this->migrator->add('property_options.location_types', config('property.location_types', []));
        $this->migrator->add('property_options.legal_statuses', config('property.legal_statuses', []));
    }

    public function down(): void
    {
        $this->migrator->delete('property_options.amenities');
        $this->migrator->delete('property_options.directions');
        $this->migrator->delete('property_options.shapes');
        $this->migrator->delete('property_options.location_types');
        $this->migrator->delete('property_options.legal_statuses');
    }
};
