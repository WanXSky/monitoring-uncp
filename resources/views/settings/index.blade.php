@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
  <div class="mb-3">
    <h3 class="fw-bold mb-0">Pengaturan</h3>
    <div class="small-muted">Ubah akun admin dan pengaturan monitoring/notifikasi.</div>
  </div>

  <div class="row g-3">
    {{-- Pengaturan Umum --}}
    <div class="col-lg-6">
      <div class="card">
        <div class="card-body">
          <div class="fw-bold mb-2">Pengaturan Umum (Akun Admin)</div>

          <form method="POST" action="{{ route('settings.update') }}">
            @csrf

            <div class="mb-3">
              <label class="form-label">Nama</label>
              <input class="form-control" name="name" value="{{ old('name', $user->name) }}" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Email</label>
              <input class="form-control" type="email" name="email" value="{{ old('email', $user->email) }}" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Password Baru (opsional)</label>
              <input class="form-control" type="password" name="password" placeholder="Minimal 8 karakter">
              <div class="small-muted mt-1">Kosongkan jika tidak ingin ganti password.</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Konfirmasi Password Baru</label>
              <input class="form-control" type="password" name="password_confirmation">
            </div>

            <button class="btn btn-primary" type="submit">Simpan Pengaturan Umum</button>
          </form>
        </div>
      </div>
    </div>

    {{-- Pengaturan Notifikasi --}}
    <div class="col-lg-6">
      <div class="card">
        <div class="card-body">
          <div class="fw-bold mb-2">Pengaturan Notifikasi / Monitoring</div>

          <form method="POST" action="{{ route('settings.update') }}">
            @csrf

            <div class="mb-3">
              <label class="form-label">Interval Cek (menit)</label>
              <input class="form-control" type="number" name="check_interval"
                     value="{{ old('check_interval', $setting->check_interval) }}"
                     min="1" max="60" required>
              <div class="small-muted mt-1">Contoh: 1 berarti cek setiap 1 menit.</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Notifikasi SSL H-berapa (hari)</label>
              <input class="form-control" type="number" name="notify_ssl_days"
                     value="{{ old('notify_ssl_days', $setting->notify_ssl_days) }}"
                     min="1" max="365" required>
              <div class="small-muted mt-1">Contoh: 7 berarti peringatan 7 hari sebelum SSL expired.</div>
            </div>

            <button class="btn btn-primary" type="submit">Simpan Pengaturan Notifikasi</button>
          </form>

          <hr>

          <div class="small-muted">
            Pastikan cron scheduler Laravel aktif agar monitoring berjalan otomatis.
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
