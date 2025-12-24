@extends('layouts.app')

@section('title', 'Settings Notifikasi')

@section('content')

<h2>Settings Notifikasi</h2>

<div class="card">
    <form method="POST" action="/settings/notify">
        @csrf

        <label>Peringatan SSL (hari sebelum kadaluarsa)</label>
        <select name="notify_ssl_days">
            <option value="1">1 Hari</option>
            <option value="3">3 Hari</option>
            <option value="7">7 Hari</option>
        </select>

        <br><br>
        <button class="btn">Simpan Perubahan</button>
    </form>
</div>

@endsection
