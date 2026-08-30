<?php

namespace Modules\Akademik\Providers;

use App\Modules\BaseModuleServiceProvider;

class AkademikServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Akademik';
    protected string $nameLower = 'akademik';

    protected function menu(): array
    {
        return [
            ['title' => 'Dashboard', 'route' => 'akd.dashboard', 'icon' => 'home', 'permission' => 'akd.dashboard.view'],

            [
                'title'     => 'Pengaturan',
                'type'      => 'dropdown',
                'icon'      => 'settings',
                'permission' => 'akd.dashboard.view',
                'children'  => [
                    ['title' => 'Periode Akademik',  'route' => 'akd.periode-akademik.index',     'active_routes' => ['akd.periode-akademik.*'],     'icon' => 'calendar-event', 'permission' => 'akd.periode-akademik.view'],
                    ['title' => 'Kalender Akademik', 'route' => 'akd.kalender-akademik.index',    'active_routes' => ['akd.kalender-akademik.*'],    'icon' => 'calendar',       'permission' => 'akd.kalender-akademik.view'],
                    ['title' => 'Ruang Kuliah',      'route' => 'akd.ruang-kuliah.index',         'active_routes' => ['akd.ruang-kuliah.*'],         'icon' => 'building-community', 'permission' => 'akd.ruang-akd.view'],
                ],
            ],

            [
                'title'     => 'Perkuliahan',
                'type'      => 'dropdown',
                'icon'      => 'book',
                'permission' => null,
                'children'  => [
                    ['title' => 'Penawaran MK',     'route' => 'akd.penawaran.index',            'active_routes' => ['akd.penawaran.*'],            'icon' => 'book',           'permission' => 'akd.penawaran.view'],
                    ['title' => 'Kelas Kuliah',     'route' => 'akd.kelas-kuliah.index',         'active_routes' => ['akd.kelas-kuliah.*'],         'icon' => 'school',         'permission' => 'akd.kelas-akd.view'],
                    ['title' => 'Jadwal Kuliah',    'route' => 'akd.jadwal-kuliah.index',        'active_routes' => ['akd.jadwal-kuliah.*'],        'icon' => 'calendar-check', 'permission' => 'akd.jadwal-akd.view'],
                    ['title' => 'Pembebanan Dosen', 'route' => 'akd.pembebanan.index',           'active_routes' => ['akd.pembebanan.*'],           'icon' => 'user-check',     'permission' => 'akd.pembebanan.view'],
                ],
            ],

            [
                'title'     => 'Mahasiswa',
                'type'      => 'dropdown',
                'icon'      => 'users',
                'permission' => null,
                'children'  => [
                    ['title' => 'Daftar Mahasiswa',   'route' => 'akd.mahasiswa.index',          'active_routes' => ['akd.mahasiswa.*'],          'icon' => 'users',                'permission' => 'akd.mahasiswa.view'],
                    ['title' => 'Cuti Mahasiswa',     'route' => 'akd.cuti.index',               'active_routes' => ['akd.cuti.*'],               'icon' => 'clock-pause',          'permission' => 'akd.cuti.view'],
                    ['title' => 'Transfer Mahasiswa', 'route' => 'akd.transfer.index',           'active_routes' => ['akd.transfer.*'],           'icon' => 'rotate-clockwise-2',    'permission' => 'akd.transfer.view'],
                    ['title' => 'Cekal Mahasiswa',    'route' => 'akd.cekal.index',              'active_routes' => ['akd.cekal.*'],              'icon' => 'shield-check',         'permission' => 'akd.cekal.view'],
                    ['title' => 'Riwayat Status',     'route' => 'akd.riwayat-status.index',     'active_routes' => ['akd.riwayat-status.*'],     'icon' => 'history',               'permission' => 'akd.riwayat-status.view'],
                    ['title' => 'Import Mahasiswa',   'route' => 'akd.mahasiswa.import.index',   'active_routes' => ['akd.mahasiswa.import.*'],   'icon' => 'database-import',       'permission' => 'akd.mahasiswa.view'],
                ],
            ],

            [
                'title'     => 'Akademik',
                'type'      => 'dropdown',
                'icon'      => 'clipboard-check',
                'permission' => null,
                'children'  => [
                    ['title' => 'KRS Mahasiswa',     'route' => 'akd.krs-mahasiswa.index',       'active_routes' => ['akd.krs-mahasiswa.*', 'akd.krs.*'], 'icon' => 'clipboard-list', 'permission' => 'akd.krs.view'],
                    ['title' => 'Nilai',             'route' => 'akd.nilai.index',               'active_routes' => ['akd.nilai.*'],              'icon' => 'chart-bar',     'permission' => 'akd.nilai.view'],
                    ['title' => 'Pembimbing',        'route' => 'akd.pembimbing-mahasiswa.index','active_routes' => ['akd.pembimbing-mahasiswa.*'],'icon' => 'user-star',      'permission' => 'akd.pembimbing-mahasiswa.view'],
                    ['title' => 'EDOM',              'route' => 'akd.edom.index',                'active_routes' => ['akd.edom.*'],               'icon' => 'message-star',  'permission' => 'akd.edom.view'],
                ],
            ],
        ];
    }
}
