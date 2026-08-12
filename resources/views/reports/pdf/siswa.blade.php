<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Profil Siswa - {{ $siswa->name }}</title>
    @include('reports.pdf._gaya-lembar')
</head>
<body>
{{-- Isinya di _lembar-siswa supaya berkas sekelas memakai lembar yang sama
     persis: dua salinan tata letak yang harus dijaga sejalan adalah cara
     tercepat membuat laporan orang tua berbeda isi dari arsip wali kelas. --}}
@include('reports.pdf._lembar-siswa')
</body>
</html>
