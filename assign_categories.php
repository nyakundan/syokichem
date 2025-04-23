// assign_categories.php
include 'components/connect.php';

// Get all products
$products = $conn->query("SELECT id, name FROM products")->fetchAll();

// Get all categories
$categories = $conn->query("SELECT id, name, slug FROM product_categories WHERE parent_id IS NOT NULL")->fetchAll();

echo "<h1>Assign Products to Categories</h1>";
echo "<form method='post'>";

// Product selector
echo "<h2>Select Product</h2>";
echo "<select name='product_id' required>";
foreach ($products as $product) {
    echo "<option value='{$product['id']}'>{$product['name']}</option>";
}
echo "</select>";

// Category selector (checkboxes)
echo "<h2>Select Categories</h2>";
foreach ($categories as $category) {
    echo "<div>
        <input type='checkbox' name='categories[]' value='{$category['id']}' id='cat_{$category['id']}'>
        <label for='cat_{$category['id']}'>{$category['name']} ({$category['slug']})</label>
    </div>";
}

echo "<button type='submit' name='assign'>Assign Categories</button>";
echo "</form>";

if (isset($_POST['assign'])) {
    $product_id = $_POST['product_id'];
    
    // Clear existing assignments
    $conn->prepare("DELETE FROM product_categories WHERE product_id = ?")->execute([$product_id]);
    
    // Add new assignments
    if (!empty($_POST['categories'])) {
        $stmt = $conn->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
        foreach ($_POST['categories'] as $category_id) {
            $stmt->execute([$product_id, $category_id]);
        }
    }
    
    echo "<p>Categories assigned successfully!</p>";
}