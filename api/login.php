<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BukuKasPro - Masuk Aplikasi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-slate-50">
    <div class="container-center">
        <div class="card-auth">
            <div class="text-center mb-6">
                <h2 class="text-2xl font-black text-slate-800">BukuKas<span class="logo-accent">Pro</span></h2>
                <p class="text-slate-400 text-sm mt-1">Silakan masuk ke dasbor pembukuan Anda</p>
            </div>

            <form action="proses/proses_login.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" required class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" required class="form-input">
                </div>
                <button type="submit" class="btn-primary">Masuk Ke Sistem</button>
            </form>
            <p class="text-center text-sm mt-4 text-slate-500">Belum mendaftar? <a href="register.php" class="text-indigo-600 font-semibold">Buat akun gratis</a></p>
        </div>
    </div>
</body>
</html>