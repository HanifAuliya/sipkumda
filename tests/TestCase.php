<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /** Satu-satunya database yang boleh dikosongkan oleh RefreshDatabase. */
    private const DATABASE_UJI = 'sipkumda_testing';

    /** Berhenti sebelum migrate:fresh berjalan, supaya tes tidak pernah menghapus data asli. */
    public function createApplication()
    {
        $app = parent::createApplication();

        $config = $app->make('config');
        $database = $config->get('database.connections.'.$config->get('database.default').'.database');

        if ($database !== self::DATABASE_UJI) {
            throw new RuntimeException("Tes dihentikan: database aktif \"{$database}\", seharusnya \"".self::DATABASE_UJI.'".');
        }

        return $app;
    }
}
