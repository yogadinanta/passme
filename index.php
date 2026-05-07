<?php

$db = new PDO('sqlite:password_manager.db');

$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create table
$db->exec("
CREATE TABLE IF NOT EXISTS passwords (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    website TEXT,
    username TEXT,
    password TEXT
)
");

// Encrypt function
function encryptPassword($data) {

    $key = "secretkey123456";

    $iv = openssl_random_pseudo_bytes(16);

    $encrypted = openssl_encrypt(
        $data,
        'AES-256-CBC',
        $key,
        0,
        $iv
    );

    return base64_encode($encrypted . '::' . $iv);
}

// Decrypt function
function decryptPassword($data) {

    $key = "secretkey123456";

    list($encrypted_data, $iv) =
        explode('::', base64_decode($data), 2);

    return openssl_decrypt(
        $encrypted_data,
        'AES-256-CBC',
        $key,
        0,
        $iv
    );
}

// Add password
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $website = $_POST['website'];
    $username = $_POST['username'];

    $password =
        encryptPassword($_POST['password']);

    $stmt = $db->prepare("
        INSERT INTO passwords
        (website, username, password)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        $website,
        $username,
        $password
    ]);

    header('Location: index.php');
    exit;
}

// Delete
if (isset($_GET['delete'])) {

    $id = $_GET['delete'];

    $stmt = $db->prepare(
        "DELETE FROM passwords WHERE id=?"
    );

    $stmt->execute([$id]);

    header('Location: index.php');
    exit;
}

// Fetch passwords
$stmt = $db->query(
    "SELECT * FROM passwords ORDER BY id DESC"
);

$passwords = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($passwords);

?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>PassMe Manager</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>

<style>

:root{
    --primary: #009B4C;
    --secondary: #F8C51B;
    --white: #ffffffff;
    --bg: #f5f7fa;
    --text: #1f2937;
}

body{
    background: var(--bg);
    min-height:100vh;
    font-family: Arial, sans-serif;
    color: var(--text);
}

.main-card{
    background: var(--white);
    border-radius:24px;
    padding:30px;
    box-shadow:0 10px 40px rgba(0,0,0,0.08);
}

h1{
    color: var(--primary);
}

.text-light{
    color:#6b7280 !important;
}

.form-control{
    border:2px solid #e5e7eb;
    border-radius:14px;
    padding:12px;
    background:white;
    color: var(--text);
}

.form-control:focus{
    border-color: var(--primary);
    box-shadow:0 0 0 0.2rem rgba(0,155,76,0.15);
}

.btn-info{
    background: var(--primary);
    border:none;
    color:white;
    border-radius:14px;
    font-weight:bold;
    transition:0.3s;
}

.btn-info:hover{
    background:#007a3c;
}

.table{
    border-radius:16px;
    overflow:hidden;
}

.table thead{
    background: var(--primary);
    color:white;
}

.table tbody tr{
    background:white;
    transition:0.2s;
}

.table tbody tr:hover{
    background:#f9fafb;
}

.table td,
.table th{
    padding:16px;
    border-bottom:1px solid #f1f1f1;
    vertical-align:middle;
}

.password-box{
    background:black;
    padding:10px 14px;
    border-radius:12px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.copy-btn{
    border:none;
    background:black;
    color: var(--primary);
    font-size:16px;
    transition:0.2s;
}

.copy-btn:hover{
    color: var(--secondary);
}

.stat-card{
    background: linear-gradient(
        135deg,
        var(--primary),
        #00b85a
    );
    color:white;
    border-radius:20px;
    padding:20px 30px;
    text-align:center;
    min-width:180px;
    box-shadow:0 10px 30px rgba(0,155,76,0.2);
}

.stat-card h3{
    font-size:36px;
    margin:0;
    font-weight:bold;
}

.stat-card small{
    opacity:0.9;
}

.btn-danger{
    border-radius:10px;
}

#searchInput{
    border-radius:14px;
}

.container{
    max-width:1200px;
}

.fa-shield-halved{
    color: var(--secondary);
}

hr{
    border-color:#e5e7eb;
}

</style>

</head>
<body>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h1 class="fw-bold">
                <i class="fa-solid fa-shield-halved"></i>
                PassMe
            </h1>

            <p class="text-light">
                Secure Password Manager
            </p>
        </div>

        <div class="stat-card">
            <h3><?= $total ?></h3>
            <small>Saved Passwords</small>
        </div>

    </div>

    <div class="main-card">

        <form method="POST" class="row g-3 mb-4">

            <div class="col-md-4">
                <input type="text"
                       name="website"
                       class="form-control"
                       placeholder="Website"
                       required>
            </div>

            <div class="col-md-3">
                <input type="text"
                       name="username"
                       class="form-control"
                       placeholder="Username"
                       required>
            </div>

            <div class="col-md-3">
                <input type="password"
                       name="password"
                       class="form-control"
                       placeholder="Password"
                       required>
            </div>

            <div class="col-md-2">
                <button class="btn btn-info w-100">
                    <i class="fa-solid fa-plus"></i>
                    Save
                </button>
            </div>

        </form>

        <input type="text"
               id="searchInput"
               class="form-control mb-4"
               placeholder="Search website...">

        <div class="table-responsive">

            <table class="table table-dark table-hover align-middle">

                <thead>
                    <tr>
                        <th>Website</th>
                        <th>Username</th>
                        <th>Password</th>
                        <th width="120">Action</th>
                    </tr>
                </thead>

                <tbody id="passwordTable">

                <?php foreach ($passwords as $row): ?>

                    <tr>

                        <td>
                            <i class="fa-solid fa-globe"></i>
                            <?= htmlspecialchars($row['website']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row['username']) ?>
                        </td>

                        <td>

                            <div class="password-box">

                                <span class="password-text">
                                    ••••••••••
                                </span>

                                <div>

                                    <button type="button"
                                            class="copy-btn"
                                            onclick="togglePassword(this,
                                            '<?= decryptPassword($row['password']) ?>')">

                                        <i class="fa-solid fa-eye"></i>

                                    </button>

                                    <button type="button"
                                            class="copy-btn"
                                            onclick="copyPassword(
                                            '<?= decryptPassword($row['password']) ?>')">

                                        <i class="fa-solid fa-copy"></i>

                                    </button>

                                </div>

                            </div>

                        </td>

                        <td>

                            <a href="?delete=<?= $row['id'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Delete password?')">

                               <i class="fa-solid fa-trash"></i>

                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<script>

function copyPassword(password){

    navigator.clipboard.writeText(password);

    alert("Password copied!");
}

function togglePassword(button, password){

    const text =
        button.parentElement.parentElement
        .querySelector('.password-text');

    if(text.innerHTML === '••••••••••'){

        text.innerHTML = password;

    }else{

        text.innerHTML = '••••••••••';
    }
}

document.getElementById('searchInput')
.addEventListener('keyup', function(){

    let value =
        this.value.toLowerCase();

    let rows =
        document.querySelectorAll('#passwordTable tr');

    rows.forEach(row => {

        row.style.display =
            row.innerText.toLowerCase()
            .includes(value)
            ? ''
            : 'none';

    });

});

</script>

</body>
</html>