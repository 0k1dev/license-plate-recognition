<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('mail.mail_mailer', 'smtp');
        $this->migrator->add('mail.mail_host', '103.7.40.50');
        $this->migrator->add('mail.mail_port', 587);
        $this->migrator->add('mail.mail_username', 'contact@thevotruyen.vn');
        $this->migrator->add('mail.mail_password', 'EnvA4Fruyw2Q1akbS5pz');
        $this->migrator->add('mail.mail_encryption', 'tls');
        $this->migrator->add('mail.mail_from_address', 'noreply@thevotruyen.vn');
        $this->migrator->add('mail.mail_from_name', 'Bất Động Sản');
    }
};
