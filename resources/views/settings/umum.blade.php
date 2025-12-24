@extends('layouts.app')

@section('title', 'Settings Umum')

@section('content')

<h2>Settings Umum</h2>

<div class="card">
    <form method="POST" action="/settings/update">
        @csrf

        <label>Interval Pengecekan (menit)</label>
        <select name="check_interval">
            <option value="1">1 Menit</option>
            <option value="5">5 Menit</option>
            <option value="10">10 Menit</option>
        </select>

        <br><br>
        <button class="btn">Simpan Perubahan</button>
    </form>
</div>

@endsection
