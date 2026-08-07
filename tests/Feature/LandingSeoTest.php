<?php

namespace Tests\Feature;

use App\Http\Controllers\LandingController;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman muka: yang dibaca pengunjung dan yang dibaca mesin pencari.
 *
 * Halaman ini satu-satunya pintu masuk bagi orang yang belum kenal produknya,
 * dan kerusakannya termasuk jenis yang paling lama tidak ketahuan: tidak ada
 * galat, tidak ada log, tidak ada yang mengeluh. Pengunjung hanya pergi.
 * Sebelum berkas ini ada, halaman muka memuat empat tautan menu yang tidak
 * punya tujuan dan sebaris bintang Markdown mentah — keduanya tayang di
 * produksi tanpa satu pun test berubah warna.
 */
class LandingSeoTest extends TestCase
{
    use RefreshDatabase;

    private function halaman(): string
    {
        return $this->get('/')->assertOk()->getContent();
    }

    /**
     * Setiap tautan #anchor harus punya elemen tujuan di halaman yang sama.
     *
     * Ini penjaga utama berkas ini. Menu lama menjanjikan "Integrasi WhatsApp",
     * "Refleksi P5", "Harga VIP", dan "FAQ", padahal hanya #fitur yang pernah
     * dibuat: empat dari lima tautannya diklik dan tidak terjadi apa-apa.
     * Pengunjung tidak menyimpulkan menunya keliru, ia menyimpulkan situsnya
     * rusak. Test ini menemukan tautan yatim itu sebelum pengunjung yang
     * menemukannya.
     */
    public function test_setiap_tautan_anchor_punya_tujuan(): void
    {
        $html = $this->halaman();

        preg_match_all('/href="#([\w-]+)"/', $html, $tautan);
        $tujuan = array_unique($tautan[1]);

        $this->assertNotEmpty($tujuan, 'halaman muka tidak punya navigasi internal sama sekali');

        foreach ($tujuan as $anchor) {
            $this->assertMatchesRegularExpression(
                '/\sid="'.preg_quote($anchor, '/').'"/',
                $html,
                "Tautan #{$anchor} tidak punya tujuan — diklik, halaman diam saja.",
            );
        }

        // Kelima seksi yang dijanjikan menu memang ada, bukan sekadar konsisten.
        foreach (['fitur', 'wa', 'p5', 'harga', 'faq'] as $seksi) {
            $this->assertStringContainsString('id="'.$seksi.'"', $html);
        }
    }

    /**
     * Blade bukan Markdown. Bintang ganda yang tersisa akan tercetak apa adanya
     * di layar — dan yang terakhir terjadi persis di kalimat pembuka hero.
     */
    public function test_tidak_ada_markdown_mentah_yang_tercetak(): void
    {
        $html = $this->halaman();

        // Hanya periksa teks yang terlihat, bukan atribut/skrip.
        $terlihat = strip_tags(preg_replace('#<(script|style)\b[^>]*>.*?</\1>#si', '', $html));

        $this->assertDoesNotMatchRegularExpression('/\*\*\S/', $terlihat, 'ada **teks** Markdown mentah di halaman');
        $this->assertStringNotContainsString('](', $terlihat, 'ada tautan Markdown mentah di halaman');
    }

    /**
     * Produk ini menyebar dari mulut ke mulut lewat grup WhatsApp guru, jadi
     * tag Open Graph bukan pemanis: tanpanya tautan yang dibagikan muncul
     * sebagai teks telanjang tanpa judul maupun gambar, dan nyaris tak diklik.
     */
    public function test_tag_open_graph_dan_twitter_lengkap(): void
    {
        $html = $this->halaman();

        foreach ([
            'og:type', 'og:url', 'og:title', 'og:description',
            'og:image', 'og:image:width', 'og:image:height', 'og:locale', 'og:site_name',
        ] as $tag) {
            $this->assertStringContainsString('property="'.$tag.'"', $html, "tag {$tag} hilang");
        }

        foreach (['twitter:card', 'twitter:title', 'twitter:description', 'twitter:image'] as $tag) {
            $this->assertStringContainsString('name="'.$tag.'"', $html, "tag {$tag} hilang");
        }

        // Alamat gambarnya harus mutlak; WhatsApp dan Facebook tidak
        // menyelesaikan alamat relatif, jadi pratinjaunya gagal tanpa suara.
        preg_match('/property="og:image" content="([^"]+)"/', $html, $m);
        $this->assertStringStartsWith('https://', $m[1] ?? '', 'og:image harus URL mutlak');
    }

    /** Gambar pratinjaunya harus benar-benar ada, dan berukuran seperti yang diakui. */
    public function test_berkas_gambar_open_graph_ada_dan_ukurannya_sesuai(): void
    {
        $berkas = public_path('og-image.png');

        $this->assertFileExists($berkas, 'og:image menunjuk berkas yang tidak ada');

        [$lebar, $tinggi] = getimagesize($berkas);
        $this->assertSame(1200, $lebar);
        $this->assertSame(630, $tinggi);
    }

    /** JSON-LD harus valid; Google menolak yang rusak tanpa memberi tahu siapa pun. */
    public function test_json_ld_valid_dan_memuat_harga(): void
    {
        $html = $this->halaman();

        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $this->assertNotEmpty($m[1] ?? '', 'tidak ada blok JSON-LD di halaman');

        $data = json_decode($m[1], true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'JSON-LD tidak valid: '.json_last_error_msg());

        $jenis = array_column($data['@graph'], '@type');
        $this->assertContains('SoftwareApplication', $jenis);
        $this->assertContains('FAQPage', $jenis);

        $app = $data['@graph'][array_search('SoftwareApplication', $jenis, true)];
        $this->assertSame((string) LandingController::HARGA_PRO, $app['offers']['price']);
        $this->assertSame('IDR', $app['offers']['priceCurrency']);
    }

    /**
     * FAQ yang dibaca Google harus sama persis dengan yang dibaca pengunjung.
     *
     * Google menganggap FAQPage yang isinya tidak tampak di halaman sebagai
     * manipulasi dan mencabut rich result-nya. Karena keduanya kini berasal
     * dari LandingController::faq(), perbedaan itu mustahil — dan test ini
     * yang menjaga agar tetap begitu bila suatu saat salah satunya ditulis
     * ulang secara manual.
     */
    public function test_faq_json_ld_sama_dengan_yang_terlihat(): void
    {
        $html = $this->halaman();

        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $data = json_decode($m[1], true);
        $jenis = array_column($data['@graph'], '@type');
        $faq = $data['@graph'][array_search('FAQPage', $jenis, true)]['mainEntity'];

        $this->assertGreaterThanOrEqual(5, count($faq), 'FAQ terlalu sedikit untuk berguna');
        $this->assertSameSize(LandingController::faq(), $faq);

        foreach ($faq as $tanya) {
            $this->assertStringContainsString(e($tanya['name']), $html,
                'pertanyaan di JSON-LD tidak tampak di halaman: '.$tanya['name']);
            $this->assertStringContainsString(e($tanya['acceptedAnswer']['text']), $html,
                'jawaban di JSON-LD tidak tampak di halaman: '.$tanya['name']);
        }
    }

    /** Dasar SEO yang tidak boleh hilang saat halaman disunting ulang. */
    public function test_meta_dasar_dan_struktur_judul(): void
    {
        $html = $this->halaman();

        $this->assertStringContainsString('<html lang="id"', $html);
        $this->assertStringContainsString('rel="canonical" href="https://walas.my.id/"', $html);
        $this->assertMatchesRegularExpression('/<meta name="robots" content="index/', $html);

        // Tepat satu <h1>. Lebih dari satu membuat mesin pencari ragu mana
        // topik utama halaman; nol membuatnya menebak sendiri.
        $this->assertSame(1, preg_match_all('/<h1[\s>]/', $html), 'halaman harus punya tepat satu <h1>');

        preg_match('#<title>(.*?)</title>#s', $html, $m);
        $panjang = mb_strlen(html_entity_decode($m[1]));
        $this->assertGreaterThan(20, $panjang);
        $this->assertLessThan(75, $panjang, 'judul terlalu panjang, akan dipotong di hasil pencarian');

        preg_match('/<meta name="description" content="([^"]*)"/', $html, $m);
        $ringkas = mb_strlen(html_entity_decode($m[1] ?? ''));
        $this->assertGreaterThan(70, $ringkas, 'deskripsi terlalu pendek untuk cuplikan hasil pencarian');
        $this->assertLessThan(200, $ringkas, 'deskripsi terlalu panjang, akan dipotong');
    }

    /**
     * Harga di halaman muka harus mengikuti kode, bukan angka yang diketik
     * lepas di Blade dan terlupakan saat harganya naik.
     */
    public function test_harga_dan_masa_gratis_mengikuti_sumbernya(): void
    {
        $html = $this->halaman();

        $this->assertStringContainsString('Rp '.number_format(LandingController::HARGA_PRO, 0, ',', '.'), $html);
        $this->assertStringContainsString('Gratis '.User::BULAN_MASA_GRATIS.' bulan', $html);
    }

    /** Pengguna yang sudah masuk tidak perlu dijual apa-apa. */
    public function test_pengguna_masuk_dialihkan_ke_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    /**
     * Formulir bertoken memuat data anak dan tidak boleh masuk hasil pencarian.
     *
     * Cukup satu guru menempelkan tautannya di laman sekolah untuk membuatnya
     * dapat ditemukan crawler. robots.txt tidak menolong di situ: ia melarang
     * perayapan, bukan pengindeksan tautan yang sudah ditemukan dari tempat
     * lain — hanya meta noindex di halamannya yang menghentikan itu.
     */
    public function test_formulir_publik_bertoken_tidak_boleh_diindeks(): void
    {
        $guru = User::factory()->create();
        $kelas = Classroom::factory()->create(['user_id' => $guru->id]);

        foreach (['public.biodata.show', 'public.reflection.show'] as $rute) {
            $this->get(route($rute, $kelas->tokenPublik()))
                ->assertOk()
                ->assertSee('name="robots" content="noindex', false);
        }
    }

    /** robots.txt dan sitemap.xml harus tersaji dan saling merujuk dengan benar. */
    public function test_robots_dan_sitemap_konsisten(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Sitemap: https://walas.my.id/sitemap.xml', $robots);

        foreach (['/admin/', '/isi-biodata/', '/refleksi-karakter/', '/a/'] as $terlarang) {
            $this->assertStringContainsString('Disallow: '.$terlarang, $robots);
        }

        $peta = simplexml_load_file(public_path('sitemap.xml'));
        $this->assertNotFalse($peta, 'sitemap.xml bukan XML yang valid');

        // SimpleXMLElement bukan array biasa: (array) $peta->url meruntuhkan
        // banyak <url> menjadi satu, sehingga sebagian besar alamat lenyap
        // sebelum sempat diperiksa.
        $alamat = [];
        foreach ($peta->url as $u) {
            $alamat[] = (string) $u->loc;
        }
        $this->assertContains('https://walas.my.id/', $alamat);

        // Sitemap tidak boleh mendaftarkan alamat yang dilarang di robots.txt;
        // Search Console melaporkannya sebagai galat, dan sinyalnya bertabrakan.
        foreach ($alamat as $a) {
            $jalur = parse_url($a, PHP_URL_PATH);
            foreach (['/admin/', '/isi-biodata/', '/refleksi-karakter/', '/a/', '/dashboard'] as $terlarang) {
                $this->assertStringNotContainsString($terlarang, $jalur,
                    "sitemap mendaftarkan {$a} yang justru dilarang robots.txt");
            }
        }
    }

    /** Manifest PWA harus ikut nama produk, bukan tertinggal di nama lama. */
    public function test_manifest_sinkron_dengan_nama_produk(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'manifest.webmanifest bukan JSON valid');
        $this->assertSame(config('app.name'), $manifest['name'],
            'nama di manifest tertinggal dari nama produk yang sekarang');
    }
}
