<?php
// populate_categories.php
include 'components/connect.php';

$categories = [
    // Medical Conditions
    [
        'name' => 'Medical Conditions',
        'slug' => 'medical-conditions',
        'description' => 'Medicines for common and specialized health conditions',
        'is_featured' => 1,
        'menu_order' => 1,
        'subcategories' => [
            [
                'name' => 'Common Conditions', 
                'slug' => 'common-conditions',
                'description' => 'Medicines for everyday health issues'
            ],
            [
                'name' => 'Specialized Care', 
                'slug' => 'specialized-care',
                'description' => 'Medicines for specific health conditions'
            ]
        ]
    ],
    
    // Vitamins & Supplements
    [
        'name' => 'Vitamins & Supplements',
        'slug' => 'vitamins-supplements',
        'description' => 'Nutritional supplements and wellness products',
        'is_featured' => 1,
        'menu_order' => 2,
        'subcategories' => [
            [
                'name' => 'Supplements', 
                'slug' => 'supplements',
                'description' => 'Various health supplements'
            ],
            [
                'name' => 'Health Foods', 
                'slug' => 'health-foods',
                'description' => 'Nutritional food products'
            ],
            [
                'name' => 'Workout Essentials', 
                'slug' => 'workout-essentials',
                'description' => 'Supplements for fitness enthusiasts'
            ],
            [
                'name' => 'Weight Management', 
                'slug' => 'weight-mgt',
                'description' => 'Products for weight control'
            ]
        ]
    ],
    
    // Personal Care
    [
        'name' => 'Personal Care',
        'slug' => 'personal-care',
        'description' => 'Hygiene and personal wellness products',
        'is_featured' => 1,
        'menu_order' => 3,
        'subcategories' => [
            [
                'name' => 'Personal Hygiene', 
                'slug' => 'personal-hygiene',
                'description' => 'Daily hygiene products'
            ],
            [
                'name' => 'Adult Toys', 
                'slug' => 'adult-toys',
                'description' => 'Adult intimacy products'
            ],
            [
                'name' => 'Sexual Wellness', 
                'slug' => 'sexual-wellness',
                'description' => 'Products for sexual health'
            ],
            [
                'name' => 'Mother Care', 
                'slug' => 'mother-care',
                'description' => 'Products for expecting and new mothers'
            ],
            [
                'name' => 'Baby Care', 
                'slug' => 'baby-care',
                'description' => 'Products for infants and toddlers'
            ],
            [
                'name' => 'Aromatherapy', 
                'slug' => 'aromatherapy',
                'description' => 'Essential oils and relaxation products'
            ]
        ]
    ],
    
    // Beauty & Skin Care
    [
        'name' => 'Beauty & Skin Care',
        'slug' => 'beauty-skin-care',
        'description' => 'Cosmetics and skin treatment products',
        'is_featured' => 1,
        'menu_order' => 4,
        'subcategories' => [
            [
                'name' => 'Body Care', 
                'slug' => 'body-care',
                'description' => 'Products for body care'
            ],
            [
                'name' => 'Hair Care', 
                'slug' => 'haircare',
                'description' => 'Hair treatment products'
            ],
            [
                'name' => 'Face Care', 
                'slug' => 'face-care',
                'description' => 'Facial care products'
            ],
            [
                'name' => 'Sun Protection', 
                'slug' => 'sun-protection',
                'description' => 'Sunscreens and UV protection'
            ],
            [
                'name' => 'Make Up', 
                'slug' => 'make-up',
                'description' => 'Cosmetic products'
            ],
            [
                'name' => 'For Children', 
                'slug' => 'for-children',
                'description' => 'Kids-safe beauty products'
            ],
            [
                'name' => 'Beard Care', 
                'slug' => 'beard-care',
                'description' => 'Products for beard maintenance'
            ]
        ]
    ],
    
    // Medical Devices
    [
        'name' => 'Medical Devices',
        'slug' => 'medical-devices',
        'description' => 'Healthcare equipment and tools',
        'is_featured' => 1,
        'menu_order' => 5,
        'subcategories' => [
            [
                'name' => 'Diagnostics', 
                'slug' => 'diagnostics',
                'description' => 'Diagnostic equipment'
            ],
            [
                'name' => 'Health Support Aids', 
                'slug' => 'health-support-aids',
                'description' => 'Mobility and support devices'
            ],
            [
                'name' => 'Self Test Kits', 
                'slug' => 'self-test-kits',
                'description' => 'Home testing kits'
            ],
            [
                'name' => 'PPEs', 
                'slug' => 'personal-protective-equipments',
                'description' => 'Protective equipment'
            ],
            [
                'name' => 'Needles & Syringes', 
                'slug' => 'needles-syringes',
                'description' => 'Medical injection supplies'
            ],
            [
                'name' => 'Weighing Scale', 
                'slug' => 'weighing-scale',
                'description' => 'Weight measurement devices'
            ],
            [
                'name' => 'Braces & Supports', 
                'slug' => 'braces-supports',
                'description' => 'Orthopedic support products'
            ]
        ]
    ]
];

try {
    // Clear existing categories if needed
    // $conn->exec("TRUNCATE TABLE categories");
    
    foreach ($categories as $category) {
        // Insert parent category
        $stmt = $conn->prepare("INSERT INTO categories 
                              (name, slug, description, is_featured, menu_order, created_at) 
                              VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $category['name'],
            $category['slug'],
            $category['description'],
            $category['is_featured'],
            $category['menu_order']
        ]);
        $parent_id = $conn->lastInsertId();
        
        // Insert subcategories
        foreach ($category['subcategories'] as $subcategory) {
            $stmt = $conn->prepare("INSERT INTO categories 
                                  (name, slug, parent_id, description, created_at) 
                                  VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([
                $subcategory['name'],
                $subcategory['slug'],
                $parent_id,
                $subcategory['description']
            ]);
        }
    }
    
    echo "Categories populated successfully!";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>