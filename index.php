<?php
require_once __DIR__ . '/db.php';

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (in_array($action, ['create', 'update'], true)) {
        $name = trim($_POST['name'] ?? '');
        $sku = strtoupper(trim($_POST['sku'] ?? ''));
        $category = trim($_POST['category'] ?? '');
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
        $priceRaw = $_POST['price'] ?? '';
        $price = filter_var($priceRaw, FILTER_VALIDATE_FLOAT);
        $threshold = filter_input(INPUT_POST, 'threshold', FILTER_VALIDATE_INT);

        if ($name === '' || $sku === '' || $category === '') {
            $error = 'Nama produk, SKU, dan kategori wajib diisi.';
        } elseif ($quantity === false || $quantity === null || $quantity < 0 || $threshold === false || $threshold === null || $threshold < 0) {
            $error = 'Jumlah stok dan batas stok minimum harus berupa angka nol atau lebih.';
        } elseif ($price === false || $price < 0) {
            $error = 'Harga harus berupa angka nol atau lebih.';
        } else {
            try {
                if ($action === 'create') {
                    $stmt = $pdo->prepare('INSERT INTO products (name, sku, category, quantity, price, threshold) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$name, $sku, $category, $quantity, $price, $threshold]);
                    $success = 'Produk berhasil ditambahkan.';
                } else {
                    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                    if (!$id) {
                        $error = 'ID produk tidak valid.';
                    } else {
                        $stmt = $pdo->prepare('UPDATE products SET name=?, sku=?, category=?, quantity=?, price=?, threshold=? WHERE id=?');
                        $stmt->execute([$name, $sku, $category, $quantity, $price, $threshold, $id]);
                        $success = 'Produk berhasil diperbarui.';
                    }
                }
            } catch (PDOException $ex) {
                if ((string)$ex->getCode() === '23000') {
                    $error = 'SKU sudah digunakan. Gunakan SKU yang berbeda.';
                } else {
                    error_log('Product save failed: ' . $ex->getMessage());
                    $error = 'Produk gagal disimpan. Periksa log aplikasi.';
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = $pdo->prepare('DELETE FROM products WHERE id=?');
            $stmt->execute([$id]);
            $success = 'Produk berhasil dihapus.';
        } else {
            $error = 'ID produk tidak valid.';
        }
    } elseif ($action === 'adjust') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $delta = filter_input(INPUT_POST, 'delta', FILTER_VALIDATE_INT);
        if (!$id || $delta === false || $delta === null || $delta === 0) {
            $error = 'Perubahan stok tidak valid.';
        } else {
            $stmt = $pdo->prepare('UPDATE products SET quantity = quantity + ? WHERE id = ? AND quantity + ? >= 0');
            $stmt->execute([$delta, $id, $delta]);
            if ($stmt->rowCount() > 0) {
                $success = $delta > 0 ? 'Stok berhasil ditambah.' : 'Stok berhasil dikurangi.';
            } else {
                $error = 'Stok tidak dapat dikurangi di bawah nol atau produk tidak ditemukan.';
            }
        }
    }
}

$editProduct = null;
if (isset($_GET['edit'])) {
    $id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id=?');
        $stmt->execute([$id]);
        $editProduct = $stmt->fetch() ?: null;
    }
}

$search = trim($_GET['q'] ?? '');
$filter = $_GET['stock'] ?? 'all';
$sql = 'SELECT * FROM products WHERE (name LIKE ? OR sku LIKE ? OR category LIKE ?)';
$params = ["%{$search}%", "%{$search}%", "%{$search}%"];
if ($filter === 'low') {
    $sql .= ' AND quantity <= threshold';
} elseif ($filter === 'available') {
    $sql .= ' AND quantity > threshold';
}
$sql .= ' ORDER BY name ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$totalProducts = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalUnits = (int)$pdo->query('SELECT COALESCE(SUM(quantity),0) FROM products')->fetchColumn();
$lowStock = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE quantity <= threshold')->fetchColumn();
$inventoryValue = (float)$pdo->query('SELECT COALESCE(SUM(quantity * price),0) FROM products')->fetchColumn();

$categories = ['Elektronik', 'Alat Tulis', 'Makanan', 'Pakaian', 'Lainnya'];
$old = $editProduct ?: ['name'=>'','sku'=>'','category'=>'Elektronik','quantity'=>0,'price'=>'','threshold'=>5];
function rupiah($amount) { return 'Rp ' . number_format((float)$amount, 0, ',', '.'); }
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>StockWise — Inventory Manager</title>
<style>
:root{--ink:#182230;--muted:#718096;--line:#e5eaf1;--bg:#f5f7fb;--green:#147d64;--green-light:#e1f5ed;--red:#b54736;--red-light:#fff0ed;--blue:#3759d7}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif}
header{background:#fff;border-bottom:1px solid var(--line)}.nav{max-width:1200px;margin:auto;padding:19px 24px;display:flex;align-items:center;justify-content:space-between}.brand{font-size:21px;font-weight:850;letter-spacing:-.6px}.brand span{color:var(--green)}.nav small{color:var(--muted)}
main{max-width:1200px;margin:32px auto;padding:0 24px 42px}.intro{display:flex;justify-content:space-between;align-items:end;gap:18px;margin-bottom:24px}h1{font-size:clamp(27px,4vw,36px);letter-spacing:-1px;margin:0 0 7px}.intro p{color:var(--muted);margin:0}.pill{background:var(--green-light);color:var(--green);border-radius:99px;padding:8px 12px;font-size:12px;font-weight:800}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}.stat,.panel{background:#fff;border:1px solid var(--line);border-radius:15px;box-shadow:0 5px 18px #18223006}.stat{padding:18px}.stat label{font-size:12px;color:var(--muted)}.stat strong{display:block;font-size:25px;margin-top:7px;letter-spacing:-.5px}.stat em{font-style:normal;font-size:11px;color:var(--muted)}
.layout{display:grid;grid-template-columns:320px 1fr;gap:18px;align-items:start}.panel{padding:20px;min-width:0}h2{font-size:17px;margin:0 0 17px}
label{display:block;font-size:12px;font-weight:750;margin:13px 0 6px}input,select{width:100%;padding:10px 11px;border:1px solid #d4dce7;border-radius:8px;background:white;color:var(--ink);font:inherit;font-size:13px}input:focus,select:focus{outline:3px solid #147d6418;border-color:var(--green)}
button,.btn{border:0;border-radius:8px;padding:9px 11px;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;cursor:pointer;font:inherit;font-size:12px;font-weight:750;white-space:nowrap}.primary{background:var(--green);color:white;width:100%;padding:12px;margin-top:18px}.soft{background:#edf1ff;color:var(--blue)}.delete{background:var(--red-light);color:var(--red)}.adjust{background:#f0f4f8;color:#364152;padding:6px 9px}
.notice{padding:11px 13px;border-radius:9px;margin-bottom:16px;font-size:13px}.ok{background:var(--green-light);color:var(--green)}.err{background:var(--red-light);color:var(--red)}
.filters{display:flex;gap:8px;margin-bottom:14px}.filters input{flex:1;min-width:100px}.filters select{max-width:145px}.table-wrap{overflow-x:auto}table{border-collapse:collapse;width:100%;font-size:12px}th{text-align:left;color:var(--muted);font-size:10px;text-transform:uppercase;letter-spacing:.5px;background:#fafbfd}td,th{padding:13px 10px;border-bottom:1px solid var(--line);vertical-align:middle}tbody tr:last-child td{border-bottom:0}.product-name{font-weight:800;font-size:13px}.sub{color:var(--muted);font-size:11px;margin-top:4px}.stock-badge{display:inline-block;border-radius:99px;padding:5px 8px;font-size:10px;font-weight:800}.low{background:#fff0d5;color:#8b5e00}.good{background:var(--green-light);color:var(--green)}.out{background:var(--red-light);color:var(--red)}.row-actions{display:flex;gap:5px;flex-wrap:wrap}.empty{text-align:center;padding:35px;color:var(--muted)}.stock-control{display:flex;gap:4px;align-items:center}.stock-control form{margin:0}.stock-control span{font-weight:800;min-width:22px;text-align:center}
@media(max-width:950px){.layout{grid-template-columns:1fr}.stats{grid-template-columns:repeat(2,1fr)}}@media(max-width:540px){main{padding:0 12px 28px}.nav{padding:16px 12px}.intro{align-items:start;flex-direction:column}.stats{gap:8px}.stat{padding:13px}.stat strong{font-size:20px}.panel{padding:14px}.filters{flex-wrap:wrap}.filters select{max-width:none;width:100%}}
</style>
</head>
<body>
<header><div class="nav"><div class="brand">Stock<span>Wise</span></div><small>Inventory management system</small></div></header>
<main>
<div class="intro"><div><h1>Inventory overview</h1><p>Kelola produk, pantau stok, dan perbarui persediaan.</p></div><div class="pill">● Sistem inventaris aktif</div></div>
<?php if($success): ?><div class="notice ok"><?=e($success)?></div><?php endif; ?>
<?php if($error): ?><div class="notice err"><?=e($error)?></div><?php endif; ?>
<section class="stats">
<div class="stat"><label>Jenis produk</label><strong><?=number_format($totalProducts)?></strong><em>Total item berbeda</em></div>
<div class="stat"><label>Total unit stok</label><strong><?=number_format($totalUnits)?></strong><em>Semua produk</em></div>
<div class="stat"><label>Stok menipis</label><strong><?=number_format($lowStock)?></strong><em>Di bawah batas minimum</em></div>
<div class="stat"><label>Nilai persediaan</label><strong style="font-size:21px"><?=e(rupiah($inventoryValue))?></strong><em>Jumlah stok × harga</em></div>
</section>
<div class="layout">
<section class="panel">
<h2><?= $editProduct ? 'Edit produk' : 'Tambah produk' ?></h2>
<form method="post">
<input type="hidden" name="action" value="<?= $editProduct ? 'update':'create' ?>">
<?php if($editProduct): ?><input type="hidden" name="id" value="<?=(int)$editProduct['id']?>"><?php endif; ?>
<label for="name">Nama produk *</label><input id="name" name="name" required maxlength="150" value="<?=e($old['name'])?>" placeholder="Contoh: Keyboard wireless">
<label for="sku">SKU / kode produk *</label><input id="sku" name="sku" required maxlength="40" value="<?=e($old['sku'])?>" placeholder="Contoh: ELEC-001">
<label for="category">Kategori *</label><select id="category" name="category" required><?php foreach($categories as $cat): ?><option value="<?=e($cat)?>" <?=$old['category']===$cat?'selected':''?>><?=e($cat)?></option><?php endforeach; ?></select>
<label for="quantity">Jumlah stok *</label><input id="quantity" name="quantity" type="number" min="0" step="1" required value="<?=e($old['quantity'])?>">
<label for="price">Harga satuan (Rp) *</label><input id="price" name="price" type="number" min="0" step="1" required value="<?=e($old['price'])?>" placeholder="150000">
<label for="threshold">Batas minimum stok *</label><input id="threshold" name="threshold" type="number" min="0" step="1" required value="<?=e($old['threshold'])?>">
<button class="primary" type="submit"><?=$editProduct?'Simpan perubahan':'Tambah produk'?></button>
<?php if($editProduct): ?><a class="btn soft" href="index.php" style="width:100%;margin-top:8px">Batal edit</a><?php endif; ?>
</form>
</section>
<section class="panel">
<h2>Daftar produk <span style="color:var(--muted);font-weight:500">(<?=count($products)?> hasil)</span></h2>
<form class="filters" method="get">
<input name="q" value="<?=e($search)?>" placeholder="Cari nama, SKU, kategori...">
<select name="stock"><option value="all" <?=$filter==='all'?'selected':''?>>Semua stok</option><option value="low" <?=$filter==='low'?'selected':''?>>Stok menipis</option><option value="available" <?=$filter==='available'?'selected':''?>>Stok aman</option></select>
<button class="soft" type="submit">Cari</button>
<?php if($search!==''||$filter!=='all'): ?><a class="btn" style="background:#f0f2f6" href="index.php">Reset</a><?php endif; ?>
</form>
<div class="table-wrap"><table><thead><tr><th>Produk</th><th>Harga</th><th>Stok</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php if(!$products): ?><tr><td colspan="5"><div class="empty">Produk tidak ditemukan. Coba kata kunci lain atau tambah produk.</div></td></tr><?php endif; ?>
<?php foreach($products as $p): $isLow=(int)$p['quantity'] <= (int)$p['threshold']; ?>
<tr>
<td><div class="product-name"><?=e($p['name'])?></div><div class="sub"><?=e($p['sku'])?> · <?=e($p['category'])?></div></td>
<td><?=e(rupiah($p['price']))?></td>
<td><div class="stock-control">
<form method="post"><input type="hidden" name="action" value="adjust"><input type="hidden" name="id" value="<?=(int)$p['id']?>"><input type="hidden" name="delta" value="-1"><button class="adjust" type="submit" aria-label="Kurangi stok">−</button></form>
<span><?=(int)$p['quantity']?></span>
<form method="post"><input type="hidden" name="action" value="adjust"><input type="hidden" name="id" value="<?=(int)$p['id']?>"><input type="hidden" name="delta" value="1"><button class="adjust" type="submit" aria-label="Tambah stok">+</button></form>
</div></td>
<td><span class="stock-badge <?=$p['quantity']===0?'out':($isLow?'low':'good')?>"><?=$p['quantity']===0?'Habis':($isLow?'Menipis':'Aman')?></span></td>
<td><div class="row-actions"><a class="btn soft" href="?edit=<?=(int)$p['id']?>">Edit</a><form method="post" onsubmit="return confirm('Hapus produk ini?')" style="margin:0"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=(int)$p['id']?>"><button class="delete" type="submit">Hapus</button></form></div></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
</section>
</div>
</main>
</body></html>
