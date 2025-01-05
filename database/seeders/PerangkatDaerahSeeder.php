<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PerangkatDaerah;

class PerangkatDaerahSeeder extends Seeder
{
    public function run()
    {
        $data = [
            'Bagian Hukum',
            'Bagian Kesejateraan Rakyat',
            'Bagian Tata Pemerintahan',
            'Bagian Perekonomian dan SDA',
            'Bagian Pembangunan',
            'Bagian Pengadaan Barang dan Jasa',
            'Bagian Organisasi',
            'Bagian Umum',
            'Bagian Protokol dan Komunikasi Pimpinan',
            'Bagian Perencanaan dan Keuangan',
            'Sekretariat DPRD',
            'Inspektorat',
            'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia Daerah',
            'Badan Perencanaan Pembangunan, Penelitian dan Pengembangan Daerah',
            'Badan Pengelola Keuangan dan Aset Daerah',
            'Badan Pengelola Pajak dan Retribusi Daerah',
            'Badan Penanggulangan Bencana Daerah',
            'Badan Kesatuan Bangsa dan Politik',
            'Dinas Pendidikan',
            'Dinas Kepemudaan, Olahraga dan Pariwisata',
            'Dinas Kesehatan',
            'Dinas Sosial, Pengendalian Penduduk, Keluarga Berencana, Pemberdayaan Perempuan dan Perlindungan Anak',
            'Dinas Kependudukan dan Pencatatan Sipil',
            'Dinas Pemberdayaan Masyarakat dan Desa',
            'Satpol PP dan Pemadam Kebakaran',
            'Dinas Penanaman Modal, PTSP dan Tenaga Kerja',
            'Dinas Perdagangan',
            'Dinas Komunikasi dan Informatika',
            'Dinas Pekerjaan Umum dan Penataan Ruang',
            'Dinas Perumahan Rakyat dan Kawasan Permukiman',
            'Dinas Pertanian',
            'Dinas Ketahanan Pangan dan Perikanan',
            'Dinas Lingkungan Hidup dan Perhubungan',
            'Dinas Perpustakaan',
            'RSUD H. Damanhuri Barabai',
            'Kecamatan Barabai',
            'Kecamatan Pandawan',
            'Kecamatan Labuan Amas Utara',
            'Kecamatan Labuan Amas Selatan',
            'Kecamatan Haruyan',
            'Kecamatan Batu Benawa',
            'Kecamatan Hantakan',
            'Kecamatan Batang Alai Utara',
            'Kecamatan Batang Alai Selatan',
            'Kecamatan Batang Alai Timur',
            'Kecamatan Limpasu',
        ];

        foreach ($data as $item) {
            PerangkatDaerah::create(['nama_perangkat_daerah' => $item]);
        }
    }
}
