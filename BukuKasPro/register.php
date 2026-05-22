<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BukuKasPro - Daftar Akun Baru</title>
    
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="landing-body">

    <div class="container-center">
        <div class="card-auth">
            
            <div class="text-center mb-8">
                <h2 class="text-3xl font-black text-slate-800 tracking-tight">
                    BukuKas<span class="logo-accent">Pro</span>
                </h2>
                <p class="text-slate-400 text-sm mt-2">
                    Langkah awal menuju manajemen finansial profesional tanpa ribet.
                </p>
            </div>

            <form action="proses/proses_registrasi.php" method="POST" class="space-y-4">
                
                <div class="form-group">
                    <label class="form-label">Nama Lengkap Pemilik</label>
                    <input type="text" name="nama" required placeholder="Nama Anda" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Username Bisnis</label>
                    <input type="text" name="username" required placeholder="Contoh: tokobanu123" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" required placeholder="••••••••" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Pilih Peran Pekerjaan (Role)</label>
                    <select   select name="role" required class="form-input">
                        <option value="cashier">Staf Kasir (Cashier Desk)</option>
                        <option value="finance">Staf Keuangan (Finance Mode)</option>
                    </select>
                </div>

                <button type="submit" class="btn-primary mt-2">
                    Buat Akun Sekarang
                </button>
            </form>
            
            <div class="text-center mt-6">
                <p class="text-sm text-slate-500">
                    Sudah memiliki akun bisnis? 
                    <a href="login.php" class="text-indigo-600 font-semibold hover:underline">
                        Masuk di sini
                    </a>
                </p>
            </div>

        </div> </div> </body>
</html>