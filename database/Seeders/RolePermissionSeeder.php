<?php

namespace Modules\Akademik\Database\Seeders;

use Modules\Account\Models\Permission;
use Modules\Account\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public static function getPermissions(): array
    {
        $perms = [];
        $perms[] = ['name' => 'akd.dashboard.view', 'category' => 'Akademik', 'sub_category' => 'Dashboard', 'description' => 'Melihat dashboard Akademik'];

        $resources = [
            // Pengaturan
            'tahun-ajaran'       => 'Tahun Ajaran',
            'periode-akademik'   => 'Periode Akademik',
            'kalender-akademik'  => 'Kalender Akademik',
            'ruang-akd'          => 'Ruang Kuliah',
            // Perkuliahan
            'penawaran'          => 'Penawaran MK',
            'pembebanan'         => 'Pembebanan Dosen',
            'kelas-akd'          => 'Kelas Kuliah',
            'jadwal-akd'         => 'Jadwal Kuliah',
            // Mahasiswa
            'biodata'            => 'Biodata Mahasiswa',
            'mahasiswa'          => 'Daftar Mahasiswa',
            'cuti'               => 'Cuti Mahasiswa',
            'transfer'           => 'Transfer Mahasiswa',
            'cekal'              => 'Cekal Mahasiswa',
            'riwayat-status'     => 'Riwayat Status',
            'status-semester'     => 'Status Semester',
            // Akademik
            'pembimbing-mahasiswa' => 'Pembimbing Mahasiswa',
            'krs'                => 'KRS',
            'nilai'              => 'Nilai',
            'edom'               => 'EDOM',
        ];

        foreach ($resources as $key => $label) {
            $actions = ['view', 'create', 'update', 'delete'];
            // Transfer has extra 'approve' action
            if ($key === 'transfer') {
                $actions[] = 'approve';
            }

            foreach ($actions as $action) {
                $perms[] = [
                    'name' => "akd.{$key}.{$action}",
                    'category' => 'Akademik',
                    'sub_category' => $label,
                    'description' => ucfirst($action === 'view' ? 'Melihat' : ($action === 'create' ? 'Menambah' : ($action === 'update' ? 'Mengubah' : ($action === 'approve' ? 'Menyetujui' : 'Menghapus')))) . " {$label}.",
                ];
            }
        }

        return $perms;
    }

    public function run(): void
    {
        Log::info('RolePermissionAkademikSeeder started');
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $tenantId = 1;

        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($tenantId);
        }

        $permissionData = self::getPermissions();

        foreach ($permissionData as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                [
                    'guard_name' => 'web',
                    'category' => $permission['category'],
                    'sub_category' => $permission['sub_category'],
                    'description' => $permission['description'],
                ]
            );
        }

        $akdPermissions = array_column($permissionData, 'name');

        foreach (['Super Administrator', 'Administrator'] as $roleName) {
            $role = Role::where('name', $roleName)->where('tenant_id', $tenantId)->first();
            if ($role) {
                $toAdd = array_values(array_diff($akdPermissions, $role->permissions->pluck('name')->all()));
                if (!empty($toAdd)) {
                    $role->givePermissionTo($toAdd);
                }
            }
        }

        Log::info('RolePermissionAkademikSeeder completed', ['permissions_count' => count($akdPermissions)]);
    }
}
