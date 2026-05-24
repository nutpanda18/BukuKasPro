<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun Baru - BukuKasPro</title>
    <link rel="stylesheet" href="./assets/css/style.css">
</head>
<body class="landing-body">

    <div class="container-center">
        <div class="card-auth space-y-4">
            
            <div class="logo" style="text-align: center; margin-bottom: 1.5rem;">
                BukuKas<span class="logo-accent">Pro</span>
                <p style="font-size: 0.85rem; font-weight: 500; color: #64748b; margin-top: 0.5rem; text-transform: none; letter-spacing: normal;">
                    Langkah awal menuju manajemen finansial profesional tanpa ribet.
                </p>
            </div>

            <form action="proses/proses_registrasi.php" method="POST" class="space-y-4">
                
                <div class="register-input-group">
                    <label class="register-label">Nama Lengkap Pemilik</label>
                    <input type="text" name="nama" class="form-input" placeholder="Nama Anda" required>
                </div>

                <div class="register-input-group">
                    <label class="register-label">Username Bisnis</label>
                    <input type="text" name="username" class="form-input" placeholder="Contoh: tokobanu123" required>
                </div>

                <div class="register-input-group">
                    <label class="register-label">Password</label>
                    <input type="password" name="password" class="form-input" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn-primary">Buat Akun Sekarang</button>
            </form>

            <p style="font-size: 0.9rem; text-align: center; color: #475569; margin-top: 1.5rem;">
                Sudah memiliki akun bisnis? <a href="login.php" style="color: #4f46e5; text-decoration: none; font-weight: 600;">Masuk di sini</a>
            </p>

        </div>
    </div>

</body>
</html>