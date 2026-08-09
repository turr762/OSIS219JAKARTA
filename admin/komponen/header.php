<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Pemilu OSIS</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* Background Utama */
        body {
            background-color: #f0f2f5; 
            color: #333;
        }
        .text-utama { color: #0d6efd !important; }
        
        /* Desain Sidebar Gelap Elegan */
        .sidebar {
            background-color: #1e293b; /* Biru abu-abu gelap */
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar-brand {
            color: #ffffff;
            font-weight: 800;
            letter-spacing: 1px;
            border-bottom: 1px solid #334155;
        }
        .sidebar a {
            color: #94a3b8; /* Abu-abu terang */
            text-decoration: none;
            padding: 12px 20px;
            display: block;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
            border-radius: 6px;
            margin: 4px 10px;
        }
        .sidebar a:hover, .sidebar a.aktif {
            background-color: #334155;
            color: #ffffff;
            border-left: 4px solid #3b82f6; /* Garis biru cerah */
        }

        /* Desain Top Navbar */
        .topbar {
            background-color: #ffffff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            padding: 15px 25px;
        }

        /* Desain Footer */
        .footer-admin {
            background-color: #e2e8f0; /* Abu-abu kalem */
            color: #475569;
            padding: 15px;
            text-align: center;
            font-size: 0.85rem;
            margin-top: auto;
        }
    </style>
</head>
<body>
<!-- Top Navbar Senada dengan Sidebar -->
<div class="topbar d-flex justify-content-between align-items-center" style="background-color: #1b2a47; box-shadow: 0 2px 4px rgba(0,0,0,.1);">
    
    <!-- Bagian Logo dengan Background Putih & Melengkung -->
    <div class="logo-brand d-flex align-items-center gap-3">
        <div style="background-color: #ffffff; padding: 6px 10px; border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <img src="../assets/img/logo_osis.png" alt="Logo OSIS" style="height: 32px; width: auto; display: block;">
        </div>
        <h5 class="mb-0 fw-bold text-white">OSIS SMPN 219 JAKARTA</h5>
    </div>

    <!-- Bagian Admin Aktif -->
    <div class="d-flex align-items-center">
    </div>
</div>

</body>