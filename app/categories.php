<?php
/**
 * SpendSmart - Categories Handler
 * CRUD operations for typed transaction categories
 */
require_once 'auth.php';
requireAuth();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetCategories();
        break;
    case 'POST':
        handleAddCategory();
        break;
    case 'PUT':
        handleEditCategory();
        break;
    case 'DELETE':
        handleDeleteCategory();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

function handleGetCategories() {
    jsonResponse(['success' => true, 'categories' => getStoredCategories()]);
}

function handleAddCategory() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    if (!isset($input['name']) || empty(trim($input['name']))) {
        jsonResponse(['success' => false, 'message' => 'Category name is required'], 400);
    }

    $name = sanitize($input['name']);
    $icon = isset($input['icon']) ? sanitize($input['icon']) : 'tag';
    $color = isset($input['color']) ? sanitize($input['color']) : '#6366F1';
    $type = isset($input['type']) ? strtolower(trim($input['type'])) : 'expense';

    if (!in_array($type, ['income', 'expense'], true)) {
        jsonResponse(['success' => false, 'message' => 'Category type must be income or expense'], 400);
    }

    $categories = getStoredCategories();
    foreach ($categories as $cat) {
        if (strtolower($cat['name']) === strtolower($name) && getCategoryType($cat) === $type) {
            jsonResponse(['success' => false, 'message' => 'Category already exists for this type'], 409);
        }
    }

    $newCategory = [
        'id' => generateId('cat'),
        'name' => $name,
        'icon' => $icon,
        'color' => $color,
        'type' => $type,
        'isDefault' => false
    ];

    $categories[] = $newCategory;
    if (!saveStoredCategories($categories)) {
        jsonResponse(['success' => false, 'message' => 'Failed to save category'], 500);
    }

    jsonResponse([
        'success' => true,
        'message' => isGuestMode() ? 'Category added (Guest Mode)' : 'Category added successfully',
        'category' => normalizeCategory($newCategory)
    ], 201);
}

function handleEditCategory() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    if (!isset($input['id']) || empty($input['id'])) {
        jsonResponse(['success' => false, 'message' => 'Category ID is required'], 400);
    }

    $categories = getStoredCategories();
    $found = false;

    foreach ($categories as &$category) {
        if ($category['id'] === $input['id']) {
            $updatedName = isset($input['name']) ? sanitize($input['name']) : $category['name'];
            $updatedType = isset($input['type']) ? strtolower(trim($input['type'])) : getCategoryType($category);

            if (!in_array($updatedType, ['income', 'expense'], true)) {
                jsonResponse(['success' => false, 'message' => 'Category type must be income or expense'], 400);
            }

            foreach ($categories as $existingCategory) {
                if ($existingCategory['id'] !== $category['id'] && strtolower($existingCategory['name']) === strtolower($updatedName) && getCategoryType($existingCategory) === $updatedType) {
                    jsonResponse(['success' => false, 'message' => 'Category already exists for this type'], 409);
                }
            }

            $category['name'] = $updatedName;
            if (isset($input['icon'])) {
                $category['icon'] = sanitize($input['icon']);
            }
            if (isset($input['color'])) {
                $category['color'] = sanitize($input['color']);
            }
            $category['type'] = $updatedType;
            $found = true;
            break;
        }
    }
    unset($category);

    if (!$found) {
        jsonResponse(['success' => false, 'message' => 'Category not found'], 404);
    }

    if (!saveStoredCategories($categories)) {
        jsonResponse(['success' => false, 'message' => 'Failed to update category'], 500);
    }

    jsonResponse(['success' => true, 'message' => isGuestMode() ? 'Category updated (Guest Mode)' : 'Category updated successfully']);
}

function handleDeleteCategory() {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['id']) || empty($input['id'])) {
        jsonResponse(['success' => false, 'message' => 'Category ID is required'], 400);
    }

    $categories = getStoredCategories();
    $initialCount = count($categories);

    $categories = array_values(array_filter($categories, function ($category) use ($input) {
        return $category['id'] !== $input['id'];
    }));

    if (count($categories) === $initialCount) {
        jsonResponse(['success' => false, 'message' => 'Category not found'], 404);
    }

    if (!saveStoredCategories($categories)) {
        jsonResponse(['success' => false, 'message' => 'Failed to delete category'], 500);
    }

    jsonResponse(['success' => true, 'message' => isGuestMode() ? 'Category deleted (Guest Mode)' : 'Category deleted successfully']);
}

function getStoredCategories() {
    // Per-user storage (guest: session, logged-in: per-user JSON file)
    return normalizeCategories(getCategoriesForCurrentSession());
}

function saveStoredCategories($categories) {
    $normalizedCategories = array_values(normalizeCategories($categories));

    return saveCategoriesForCurrentSession($normalizedCategories);
}

function normalizeCategories($categories) {
    return array_map('normalizeCategory', is_array($categories) ? $categories : []);
}

function normalizeCategory($category) {
    if (!is_array($category)) {
        $category = [];
    }

    $category['id'] = isset($category['id']) ? $category['id'] : generateId('cat');
    $category['name'] = isset($category['name']) ? $category['name'] : 'Unnamed';
    $category['icon'] = isset($category['icon']) ? $category['icon'] : 'tag';
    $category['color'] = isset($category['color']) ? $category['color'] : '#6366F1';
    $category['type'] = getCategoryType($category);
    $category['isDefault'] = !empty($category['isDefault']);

    return $category;
}

function getCategoryType($category) {
    $type = isset($category['type']) ? strtolower(trim((string) $category['type'])) : '';
    if (in_array($type, ['income', 'expense'], true)) {
        return $type;
    }

    return inferCategoryType($category);
}

function inferCategoryType($category) {
    $name = strtolower(isset($category['name']) ? (string) $category['name'] : '');
    $icon = strtolower(isset($category['icon']) ? (string) $category['icon'] : '');
    $incomeIcons = ['wallet', 'money-bill'];
    $incomeKeywords = ['income', 'salary', 'freelance', 'bonus', 'allowance', 'interest', 'refund', 'revenue'];

    if (in_array($icon, $incomeIcons, true)) {
        return 'income';
    }

    foreach ($incomeKeywords as $keyword) {
        if ($name !== '' && strpos($name, $keyword) !== false) {
            return 'income';
        }
    }

    return 'expense';
}
?>