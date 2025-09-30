<?php
require_once 'api-functions.php';

// Get property ID from URL
$property_id = isset($_GET['id']) ? $_GET['id'] : '';

// Initialize API and get property
$api = new SimplePropertyAPI();
$result = $api->getPropertyByListingKey($property_id);

// Handle errors
if (isset($result['error'])) {
    include "header.php";
    echo '<div class="container mx-auto px-4 py-8 mt-16">';
    echo '<div class="text-center py-16">';
    echo '<div class="max-w-md mx-auto">';
    echo '<i class="fas fa-exclamation-triangle text-6xl text-red-300 mb-4"></i>';
    echo '<h3 class="text-xl font-semibold text-red-600 mb-2">Error</h3>';
    echo '<p class="text-gray-500">' . $result['error'] . '</p>';
    echo '<a href="properties.php" class="mt-4 inline-block bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">Back to Properties</a>';
    echo '</div></div></div>';
    include "footer.php";
    exit;
}

$property = $result['data'];

// Process property images - only from API
$propertyImages = [];
if (isset($property['Media']) && !empty($property['Media'])) {
    foreach ($property['Media'] as $media) {
        if ($media['MediaCategory'] === 'Photo') {
            $propertyImages[] = [
                'url' => $media['MediaURL'],
                'description' => $media['ShortDescription'] ?: 'Property Photo'
            ];
        }
    }
}

include "header.php";
?>

<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    cream: '#ddcabc',
                    'cream-light': '#e8d7ca',
                    'cream-dark': '#c8b5a5'
                }
            }
        }
    }
</script>

<style>
    /* Modern Image Gallery Styles */
    .gallery-main-container {
        position: relative;
        background: #000;
        border-radius: 12px;
        overflow: hidden;
    }
    
    .gallery-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 2px;
        height: 500px;
    }
    
    .gallery-main-image {
        position: relative;
        grid-row: span 2;
        overflow: hidden;
        cursor: pointer;
    }
    
    .gallery-thumbnail {
        position: relative;
        overflow: hidden;
        cursor: pointer;
        background: #f3f4f6;
    }
    
    .gallery-thumbnail img,
    .gallery-main-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .gallery-thumbnail:hover img,
    .gallery-main-image:hover img {
        transform: scale(1.05);
    }
    
    .gallery-counter {
        position: absolute;
        bottom: 20px;
        right: 20px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 14px;
        z-index: 10;
    }
    
    .view-all-photos-btn {
        position: absolute;
        bottom: 20px;
        left: 20px;
        background: white;
        color: #333;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        z-index: 10;
        border: 1px solid #ddd;
    }
    
    .view-all-photos-btn:hover {
        background: #f3f4f6;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    /* Modal Gallery Styles */
    .modal-gallery {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.95);
        z-index: 9999;
        overflow: hidden;
    }
    
    .modal-gallery.active {
        display: flex;
        flex-direction: column;
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        background: rgba(0, 0, 0, 0.8);
        color: white;
    }
    
    .modal-close {
        background: transparent;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        padding: 8px;
        transition: opacity 0.3s;
    }
    
    .modal-close:hover {
        opacity: 0.7;
    }
    
    .modal-content {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        padding: 20px;
    }
    
    .modal-image-container {
        max-width: 90%;
        max-height: 80vh;
        position: relative;
    }
    
    .modal-image-container img {
        width: 100%;
        height: auto;
        max-height: 80vh;
        object-fit: contain;
    }
    
    .modal-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.9);
        color: #333;
        border: none;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
        font-size: 20px;
    }
    
    .modal-nav:hover {
        background: white;
        transform: translateY(-50%) scale(1.1);
    }
    
    .modal-nav.prev {
        left: 20px;
    }
    
    .modal-nav.next {
        right: 20px;
    }
    
    .modal-thumbnails {
        display: flex;
        gap: 8px;
        padding: 20px;
        background: rgba(0, 0, 0, 0.8);
        overflow-x: auto;
        justify-content: center;
    }
    
    .modal-thumb {
        width: 80px;
        height: 60px;
        cursor: pointer;
        opacity: 0.6;
        transition: opacity 0.3s;
        border: 2px solid transparent;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .modal-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .modal-thumb.active,
    .modal-thumb:hover {
        opacity: 1;
        border-color: white;
    }
    
    /* Property Info Card Styles */
    .property-info-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 24px;
        margin-bottom: 24px;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s ease;
        border: 1px solid #e5e7eb;
    }
    
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    }
    
    /* Tab Styles */
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
        animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .tab-btn {
        position: relative;
        padding: 12px 24px;
        font-weight: 500;
        color: #6b7280;
        transition: all 0.3s ease;
        border-bottom: 3px solid transparent;
    }
    
    .tab-btn.active {
        color: #1f2937;
        border-bottom-color: #ddcabc;
    }
    
    .tab-btn:hover:not(.active) {
        color: #4b5563;
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        .gallery-grid {
            grid-template-columns: 1fr;
            height: 300px;
        }
        
        .gallery-thumbnail {
            display: none;
        }
        
        .modal-thumbnails {
            padding: 10px;
        }
        
        .modal-thumb {
            width: 60px;
            height: 45px;
        }
    }
    
    /* No Image Placeholder */
    .no-image-placeholder {
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        color: #6b7280;
        height: 500px;
        border-radius: 12px;
    }
</style>

<!-- Main Content -->
<main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 mt-16 max-w-7xl">
    
    <!-- Property Header -->
    <div class="mb-6">
        <?php
        $street_address = '';
        $city_state_zip = '';

        if (!empty($property['UnparsedAddress'])) {
            $address = htmlspecialchars($property['UnparsedAddress']);
            $address_parts = array_map('trim', explode(',', $address, 2));
            $street_address = $address_parts[0];
            $city_state_zip = isset($address_parts[1]) ? $address_parts[1] : ''; 
        } else {
            $street_address = trim(htmlspecialchars(($property['StreetNumber'] ?? '') . ' ' . ($property['StreetName'] ?? '')));
            if (empty($street_address)) {
                $street_address = 'Address not available';
            }
            $city = $property['City'] ?? '';
            $state = $property['StateOrProvince'] ?? '';
            $zip = $property['PostalCode'] ?? '';
            $city_state_zip = trim("$city, $state $zip");
        }
        ?>
        
        <div class="flex flex-col md:flex-row md:items-start md:justify-between">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2"><?php echo $street_address; ?></h1>
                <p class="text-lg text-gray-600 mb-2"><?php echo $city_state_zip; ?></p>
                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                    <span class="flex items-center">
                        <i class="fas fa-home mr-2"></i>
                        <?php echo $property['PropertyType'] ?? 'Residential'; ?>
                    </span>
                    <span class="flex items-center">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        <?php echo $property['DaysOnMarket'] ?? '0'; ?> days on market
                    </span>
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <?php echo $property['StandardStatus'] ?? 'Active'; ?>
                    </span>
                </div>
            </div>
            <div class="mt-4 md:mt-0 text-left md:text-right">
                <div class="text-3xl md:text-4xl font-bold text-gray-900 mb-1">
                    $<?php echo number_format($property['ListPrice'] ?? 0); ?>
                </div>
                <div class="text-sm text-gray-500">
                    <?php 
                    $price = $property['ListPrice'] ?? 0;
                    $sqft = $property['LivingArea'] ?? 0;
                    
                    if ($price > 0 && $sqft > 0) {
                        echo '$' . number_format($price / $sqft) . '/sqft';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modern Image Gallery -->
    <div class="mb-8">
        <?php if (!empty($propertyImages)): ?>
            <div class="gallery-main-container">
                <div class="gallery-grid" id="galleryGrid">
                    <!-- Main Image -->
                    <div class="gallery-main-image" onclick="openModal(0)">
                        <img src="<?php echo htmlspecialchars($propertyImages[0]['url']); ?>" 
                             alt="<?php echo htmlspecialchars($propertyImages[0]['description']); ?>">
                    </div>
                    
                    <!-- Thumbnail Images -->
                    <?php for ($i = 1; $i < min(5, count($propertyImages)); $i++): ?>
                        <div class="gallery-thumbnail" onclick="openModal(<?php echo $i; ?>)">
                            <img src="<?php echo htmlspecialchars($propertyImages[$i]['url']); ?>" 
                                 alt="<?php echo htmlspecialchars($propertyImages[$i]['description']); ?>">
                        </div>
                    <?php endfor; ?>
                    
                    <!-- Fill empty slots with placeholder -->
                    <?php for ($i = count($propertyImages); $i < 5; $i++): ?>
                        <div class="gallery-thumbnail" style="background: #f3f4f6;">
                            <div class="flex items-center justify-center h-full text-gray-400">
                                <i class="fas fa-image text-3xl"></i>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <button class="view-all-photos-btn" onclick="openModal(0)">
                    <i class="fas fa-images mr-2"></i>
                    View All <?php echo count($propertyImages); ?> Photos
                </button>
                
                <div class="gallery-counter">
                    1 / <?php echo count($propertyImages); ?>
                </div>
            </div>
            
            <!-- Modal Gallery -->
            <div id="modalGallery" class="modal-gallery">
                <div class="modal-header">
                    <div class="modal-title">
                        <span id="modalCounter">1 / <?php echo count($propertyImages); ?></span>
                    </div>
                    <button class="modal-close" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="modal-content">
                    <button class="modal-nav prev" onclick="navigateModal(-1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    
                    <div class="modal-image-container">
                        <img id="modalImage" src="" alt="">
                    </div>
                    
                    <button class="modal-nav next" onclick="navigateModal(1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                
                <div class="modal-thumbnails" id="modalThumbnails">
                    <?php foreach ($propertyImages as $index => $image): ?>
                        <div class="modal-thumb <?php echo $index === 0 ? 'active' : ''; ?>" 
                             onclick="setModalImage(<?php echo $index; ?>)">
                            <img src="<?php echo htmlspecialchars($image['url']); ?>" 
                                 alt="<?php echo htmlspecialchars($image['description']); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="no-image-placeholder">
                <i class="fas fa-image text-6xl mb-4"></i>
                <h3 class="text-2xl font-semibold mb-2">No Images Available</h3>
                <p class="text-gray-500">Property images are not currently available</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Key Stats -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <div class="stat-card">
            <i class="fas fa-bed text-3xl text-cream-dark mb-3"></i>
            <div class="text-2xl font-bold text-gray-900"><?php echo $property['BedroomsTotal'] ?? '-'; ?></div>
            <div class="text-sm text-gray-600">Bedrooms</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-bath text-3xl text-cream-dark mb-3"></i>
            <div class="text-2xl font-bold text-gray-900">
                <?php echo $property['BathroomsTotalInteger'] ?? $property['BathroomsTotalDecimal'] ?? '-'; ?>
            </div>
            <div class="text-sm text-gray-600">Bathrooms</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-expand-arrows-alt text-3xl text-cream-dark mb-3"></i>
            <div class="text-2xl font-bold text-gray-900">
                <?php 
                $sqft = $property['LivingArea'] ?? 0; 
                echo $sqft > 0 ? number_format($sqft) : '-'; 
                ?>
            </div>
            <div class="text-sm text-gray-600">Sq Ft</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-car text-3xl text-cream-dark mb-3"></i>
            <div class="text-2xl font-bold text-gray-900">
                <?php echo $property['ParkingTotal'] ?? $property['GarageSpaces'] ?? '-'; ?>
            </div>
            <div class="text-sm text-gray-600">Parking</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-calendar text-3xl text-cream-dark mb-3"></i>
            <div class="text-2xl font-bold text-gray-900"><?php echo $property['YearBuilt'] ?? '-'; ?></div>
            <div class="text-sm text-gray-600">Year Built</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-chart-area text-3xl text-cream-dark mb-3"></i>
            <div class="text-2xl font-bold text-gray-900">
                <?php 
                $acres = $property['LotSizeAcres'] ?? 0;
                echo $acres > 0 ? number_format($acres, 2) : '-';
                ?>
            </div>
            <div class="text-sm text-gray-600">Acres</div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column - Property Details -->
        <div class="lg:col-span-2">
            
            <!-- Property Tabs -->
            <div class="property-info-card">
                <div class="border-b border-gray-200 mb-6">
                    <nav class="flex space-x-8">
                        <button class="tab-btn active" data-tab="overview">Overview</button>
                        <button class="tab-btn" data-tab="details">Details</button>
                        <button class="tab-btn" data-tab="features">Features</button>
                    </nav>
                </div>

                <!-- Overview Tab -->
                <div id="overview" class="tab-content active">
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Property Description</h3>
                    <p class="text-gray-700 leading-relaxed mb-6">
                        <?php 
                        $description = $property['PublicRemarks'] ?? $property['SyndicationRemarks'] ?? '';
                        if (empty($description)) {
                            $bedrooms = $property['BedroomsTotal'] ?? 'multiple';
                            $bathrooms = $property['BathroomsTotalInteger'] ?? $property['BathroomsTotalDecimal'] ?? 'multiple';
                            $sqft = $property['LivingArea'] ?? 0;
                            $city = $property['City'] ?? 'this area';
                            
                            $description = "Beautiful property featuring {$bedrooms} bedrooms and {$bathrooms} bathrooms";
                            if ($sqft > 0) {
                                $description .= " with " . number_format($sqft) . " square feet of living space";
                            }
                            $description .= ". Located in {$city}, this home offers modern amenities and excellent value.";
                        }
                        echo htmlspecialchars($description); 
                        ?>
                    </p>
                    
                    <!-- Quick Facts Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-4">Property Information</h4>
                            <div class="space-y-3">
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600">MLS #</span>
                                    <span class="font-medium"><?php echo $property['ListingId'] ?? 'N/A'; ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600">Property Type</span>
                                    <span class="font-medium"><?php echo $property['PropertyType'] ?? 'N/A'; ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600">Property Subtype</span>
                                    <span class="font-medium"><?php echo $property['PropertySubType'] ?? 'N/A'; ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600">Stories</span>
                                    <span class="font-medium"><?php echo $property['StoriesTotal'] ?? $property['Stories'] ?? 'N/A'; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-4">Financial Information</h4>
                            <div class="space-y-3">
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600">List Price</span>
                                    <span class="font-medium">$<?php echo number_format($property['ListPrice'] ?? 0); ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600">Annual Tax</span>
                                    <span class="font-medium">
                                        <?php 
                                        $tax = $property['TaxAnnualAmount'] ?? 0;
                                        echo $tax > 0 ? '$' . number_format($tax) : 'N/A'; 
                                        ?>
                                    </span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600">HOA Fee</span>
                                    <span class="font-medium">
                                        <?php 
                                        $hoa = $property['AssociationFee'] ?? 0;
                                        $freq = $property['AssociationFeeFrequency'] ?? '';
                                        echo $hoa > 0 ? '$' . number_format($hoa) . ($freq ? '/' . $freq : '') : 'None';
                                        ?>
                                    </span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600">Price/Sqft</span>
                                    <span class="font-medium">
                                        <?php 
                                        if ($price > 0 && $sqft > 0) {
                                            echo '$' . number_format($price / $sqft);
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details Tab -->
                <div id="details" class="tab-content">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Property Details</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h4 class="text-lg font-semibold mb-4">Interior Details</h4>
                            <div class="space-y-3">
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600">Bedrooms</span>
                                    <span class="font-medium"><?php echo $property['BedroomsTotal'] ?? 'N/A'; ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600">Full Bathrooms</span>
                                    <span class="font-medium"><?php echo $property['BathroomsFull'] ?? 'N/A'; ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600">Half Bathrooms</span>
                                    <span class="font-medium"><?php echo $property['BathroomsHalf'] ?? '0'; ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600">Living Area</span>
                                    <span class="font-medium">
                                        <?php 
                                        $sqft = $property['LivingArea'] ?? 0;
                                        echo $sqft > 0 ? number_format($sqft) . ' sqft' : 'N/A';
                                        ?>
                                    </span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600">Fireplace</span>
                                    <span class="font-medium">
                                        <?php echo isset($property['FireplaceYN']) && $property['FireplaceYN'] ? 'Yes' : 'No'; ?>
                                    </span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600">Cooling</span>
                                    <span class="font-medium">
                                        <?php echo isset($property['CoolingYN']) && $property['CoolingYN'] ? 'Central Air' : 'None'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="text-lg font-semibold mb-4">Exterior Details</h4>
                            <div class="space-y-3">
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600">Lot Size</span>
                                    <span class="font-medium">
                                        <?php 
                                        $acres = $property['LotSizeAcres'] ?? 0;
                                        echo $acres > 0 ? number_format($acres, 2) . ' acres' : 'N/A';
                                        ?>
                                    </span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600">Parking Spaces</span>
                                    <span class="font-medium">
                                        <?php echo $property['ParkingTotal'] ?? $property['GarageSpaces'] ?? 'N/A'; ?>
                                    </span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600">Garage Spaces</span>
                                    <span class="font-medium"><?php echo $property['GarageSpaces'] ?? '0'; ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600">Pool</span>
                                    <span class="font-medium">
                                        <?php echo isset($property['PoolPrivateYN']) && $property['PoolPrivateYN'] ? 'Private Pool' : 'No'; ?>
                                    </span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600">Waterfront</span>
                                    <span class="font-medium">
                                        <?php echo isset($property['WaterfrontYN']) && $property['WaterfrontYN'] ? 'Yes' : 'No'; ?>
                                    </span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600">View</span>
                                    <span class="font-medium">
                                        <?php echo isset($property['ViewYN']) && $property['ViewYN'] ? 'Yes' : 'No'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional Details -->
                    <div class="mt-8">
                        <h4 class="text-lg font-semibold mb-4">Additional Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-600">Year Built</span>
                                <span class="font-medium"><?php echo $property['YearBuilt'] ?? 'N/A'; ?></span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-600">Days on Market</span>
                                <span class="font-medium"><?php echo $property['DaysOnMarket'] ?? '0'; ?> days</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-600">MLS Status</span>
                                <span class="font-medium"><?php echo $property['MlsStatus'] ?? 'Active'; ?></span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-600">Listing Date</span>
                                <span class="font-medium">
                                    <?php 
                                    if (isset($property['ListingContractDate'])) {
                                        echo date('M d, Y', strtotime($property['ListingContractDate']));
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Features Tab -->
                <div id="features" class="tab-content">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Features & Amenities</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Interior Features -->
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-3">
                                <i class="fas fa-home mr-2 text-cream-dark"></i>Interior Features
                            </h4>
                            <ul class="space-y-2 text-gray-600">
                                <?php 
                                $hasInteriorFeatures = false;
                                if (isset($property['Flooring']) && is_array($property['Flooring']) && !empty($property['Flooring'])) {
                                    foreach ($property['Flooring'] as $floor) {
                                        echo '<li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>' . htmlspecialchars($floor) . '</li>';
                                        $hasInteriorFeatures = true;
                                    }
                                }
                                
                                if (isset($property['LaundryFeatures']) && is_array($property['LaundryFeatures']) && !empty($property['LaundryFeatures'])) {
                                    foreach ($property['LaundryFeatures'] as $feature) {
                                        echo '<li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>' . htmlspecialchars($feature) . '</li>';
                                        $hasInteriorFeatures = true;
                                    }
                                }
                                
                                if (!$hasInteriorFeatures) {
                                    echo '<li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Modern interior</li>';
                                    echo '<li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Updated fixtures</li>';
                                }
                                ?>
                            </ul>
                        </div>
                        
                        <!-- Exterior Features -->
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-3">
                                <i class="fas fa-tree mr-2 text-cream-dark"></i>Exterior Features
                            </h4>
                            <ul class="space-y-2 text-gray-600">
                                <?php if ($property['LotSizeAcres'] ?? 0 > 0): ?>
                                    <li class="flex items-center">
                                        <i class="fas fa-check text-green-500 mr-2"></i>
                                        <?php echo number_format($property['LotSizeAcres'], 2); ?> acre lot
                                    </li>
                                <?php endif; ?>
                                
                                <?php if ($property['ParkingTotal'] ?? $property['GarageSpaces'] ?? 0 > 0): ?>
                                    <li class="flex items-center">
                                        <i class="fas fa-check text-green-500 mr-2"></i>
                                        <?php echo $property['ParkingTotal'] ?? $property['GarageSpaces']; ?> parking spaces
                                    </li>
                                <?php endif; ?>
                                
                                <?php if (isset($property['PoolPrivateYN']) && $property['PoolPrivateYN']): ?>
                                    <li class="flex items-center">
                                        <i class="fas fa-check text-green-500 mr-2"></i>Private pool
                                    </li>
                                <?php endif; ?>
                                
                                <?php if (isset($property['WaterfrontYN']) && $property['WaterfrontYN']): ?>
                                    <li class="flex items-center">
                                        <i class="fas fa-check text-green-500 mr-2"></i>Waterfront property
                                    </li>
                                <?php endif; ?>
                                
                                <?php if (isset($property['ViewYN']) && $property['ViewYN']): ?>
                                    <li class="flex items-center">
                                        <i class="fas fa-check text-green-500 mr-2"></i>Scenic views
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        
                        <!-- Community Features -->
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-3">
                                <i class="fas fa-users mr-2 text-cream-dark"></i>Community Features
                            </h4>
                            <ul class="space-y-2 text-gray-600">
                                <?php if (isset($property['AssociationYN']) && $property['AssociationYN']): ?>
                                    <li class="flex items-center">
                                        <i class="fas fa-check text-green-500 mr-2"></i>HOA community
                                    </li>
                                <?php endif; ?>
                                
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-2"></i>Established neighborhood
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-2"></i>Close to amenities
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Virtual Tour Section -->
            <?php if (isset($property['VirtualTourURLUnbranded']) || isset($property['VirtualTourURLBranded'])): ?>
            <div class="property-info-card">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Virtual Tour</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 text-center">
                        <i class="fas fa-vr-cardboard text-4xl text-blue-600 mb-4"></i>
                        <h4 class="text-xl font-bold text-gray-900 mb-2">3D Virtual Tour</h4>
                        <p class="text-gray-600 mb-4">Experience an immersive walkthrough</p>
                        <a href="<?php echo $property['VirtualTourURLUnbranded'] ?? $property['VirtualTourURLBranded'] ?? '#'; ?>" 
                           target="_blank"
                           class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                            Start Tour
                        </a>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-6 text-center">
                        <i class="fas fa-video text-4xl text-purple-600 mb-4"></i>
                        <h4 class="text-xl font-bold text-gray-900 mb-2">Video Walkthrough</h4>
                        <p class="text-gray-600 mb-4">Guided tour with details</p>
                        <button class="bg-purple-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-purple-700 transition-colors">
                            Watch Video
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column - Contact & Tools -->
        <div class="lg:col-span-1">
            <div class="sticky top-24 space-y-6">
                
                <!-- Contact Agent Card -->
                <div class="property-info-card">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Contact Agent</h3>
                    
                    <!-- Agent Info -->
                    <div class="flex items-start mb-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-cream to-cream-dark rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-user text-2xl text-white"></i>
                        </div>
                        <div class="flex-1">
                            <div class="font-bold text-lg text-gray-900">
                                <?php echo htmlspecialchars($property['ListAgentFullName'] ?? 'Listing Agent'); ?>
                            </div>
                            <div class="text-sm text-gray-600">
                                <?php echo htmlspecialchars($property['ListOfficeName'] ?? 'Real Estate Office'); ?>
                            </div>
                            <div class="flex items-center mt-1">
                                <div class="flex text-yellow-400">
                                    <?php for($i = 0; $i < 5; $i++): ?>
                                        <i class="fas fa-star text-xs"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-xs text-gray-600 ml-2">5.0 (50+ reviews)</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="grid grid-cols-2 gap-3 mb-6">
                        <button class="bg-cream text-gray-900 font-semibold py-3 px-4 rounded-lg hover:bg-cream-dark transition-colors text-sm">
                            <i class="fas fa-calendar-alt mr-1"></i>
                            Tour
                        </button>
                        <button class="bg-gray-900 text-white font-semibold py-3 px-4 rounded-lg hover:bg-gray-800 transition-colors text-sm">
                            <i class="fas fa-phone mr-1"></i>
                            Call
                        </button>
                    </div>
                    
                    <!-- Contact Form -->
                    <form method="POST" action="contact_handler.php" class="space-y-4">
                        <input type="hidden" name="property_id" value="<?php echo htmlspecialchars($property['ListingKey'] ?? ''); ?>">
                        
                        <input type="text" name="name" placeholder="Your Name" required
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-cream-dark focus:border-transparent">
                        
                        <input type="email" name="email" placeholder="Email" required
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-cream-dark focus:border-transparent">
                        
                        <input type="tel" name="phone" placeholder="Phone (optional)"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-cream-dark focus:border-transparent">
                        
                        <textarea name="message" placeholder="I'm interested in this property" rows="3"
                                  class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-cream-dark focus:border-transparent resize-none"></textarea>
                        
                        <button type="submit" class="w-full bg-cream text-gray-900 font-bold py-3 px-6 rounded-lg hover:bg-cream-dark transition-colors">
                            Send Message
                        </button>
                    </form>
                </div>
                
                <!-- Mortgage Calculator -->
                <div class="property-info-card">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Monthly Payment Calculator</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Home Price</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                                <input type="number" id="homePrice" value="<?php echo $property['ListPrice'] ?? 0; ?>"
                                       class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cream-dark">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Down Payment</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                                <input type="number" id="downPayment" value="<?php echo round(($property['ListPrice'] ?? 0) * 0.2); ?>"
                                       class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cream-dark">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Interest Rate</label>
                            <div class="relative">
                                <input type="number" id="interestRate" value="7.0" step="0.1"
                                       class="w-full pr-8 pl-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cream-dark">
                                <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500">%</span>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Loan Term</label>
                            <select id="loanTerm" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cream-dark">
                                <option value="30">30 years</option>
                                <option value="15">15 years</option>
                            </select>
                        </div>
                        
                        <button onclick="calculatePayment()" class="w-full bg-gray-900 text-white py-3 rounded-lg hover:bg-gray-800 transition-colors font-semibold">
                            Calculate
                        </button>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="text-sm text-gray-600 mb-2">Estimated Monthly Payment</div>
                        <div id="monthlyPayment" class="text-3xl font-bold text-gray-900">$0</div>
                        <p class="text-xs text-gray-500 mt-2">Principal & Interest only</p>
                    </div>
                </div>
                
                <!-- Share Buttons -->
                <div class="property-info-card">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Share This Property</h3>
                    <div class="flex space-x-3">
                        <button class="flex-1 bg-blue-600 text-white py-2 px-3 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </button>
                        <button class="flex-1 bg-sky-500 text-white py-2 px-3 rounded-lg hover:bg-sky-600 transition-colors">
                            <i class="fab fa-twitter"></i>
                        </button>
                        <button class="flex-1 bg-green-600 text-white py-2 px-3 rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fab fa-whatsapp"></i>
                        </button>
                        <button class="flex-1 bg-gray-600 text-white py-2 px-3 rounded-lg hover:bg-gray-700 transition-colors">
                            <i class="fas fa-envelope"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Image Gallery Data
<?php if (!empty($propertyImages)): ?>
const propertyImages = <?php echo json_encode($propertyImages); ?>;
let currentModalIndex = 0;

// Modal Gallery Functions
function openModal(index) {
    const modal = document.getElementById('modalGallery');
    currentModalIndex = index;
    updateModalImage();
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    const modal = document.getElementById('modalGallery');
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
}

function navigateModal(direction) {
    currentModalIndex += direction;
    if (currentModalIndex < 0) {
        currentModalIndex = propertyImages.length - 1;
    } else if (currentModalIndex >= propertyImages.length) {
        currentModalIndex = 0;
    }
    updateModalImage();
}

function setModalImage(index) {
    currentModalIndex = index;
    updateModalImage();
}

function updateModalImage() {
    const modalImage = document.getElementById('modalImage');
    const modalCounter = document.getElementById('modalCounter');
    const thumbnails = document.querySelectorAll('.modal-thumb');
    
    modalImage.src = propertyImages[currentModalIndex].url;
    modalImage.alt = propertyImages[currentModalIndex].description;
    modalCounter.textContent = `${currentModalIndex + 1} / ${propertyImages.length}`;
    
    // Update thumbnail active states
    thumbnails.forEach((thumb, index) => {
        if (index === currentModalIndex) {
            thumb.classList.add('active');
        } else {
            thumb.classList.remove('active');
        }
    });
    
    // Scroll thumbnail into view
    if (thumbnails[currentModalIndex]) {
        thumbnails[currentModalIndex].scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    }
}

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('modalGallery');
    if (modal && modal.classList.contains('active')) {
        if (e.key === 'ArrowLeft') {
            navigateModal(-1);
        } else if (e.key === 'ArrowRight') {
            navigateModal(1);
        } else if (e.key === 'Escape') {
            closeModal();
        }
    }
});

// Update gallery counter on main gallery
document.addEventListener('DOMContentLoaded', function() {
    const galleryCounter = document.querySelector('.gallery-counter');
    if (galleryCounter) {
        galleryCounter.textContent = `1 / ${propertyImages.length}`;
    }
});
<?php endif; ?>

// Tab Functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Update button states
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            // Update content visibility
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            const targetContent = document.getElementById(targetTab);
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    });
    
    // Initialize mortgage calculator
    calculatePayment();
});

// Mortgage Calculator
function calculatePayment() {
    const homePrice = parseFloat(document.getElementById('homePrice').value) || 0;
    const downPayment = parseFloat(document.getElementById('downPayment').value) || 0;
    const interestRate = parseFloat(document.getElementById('interestRate').value) || 0;
    const loanTerm = parseInt(document.getElementById('loanTerm').value) || 30;
    
    const principal = homePrice - downPayment;
    const monthlyRate = interestRate / 100 / 12;
    const numPayments = loanTerm * 12;
    
    let monthlyPayment = 0;
    
    if (monthlyRate > 0) {
        monthlyPayment = principal * (monthlyRate * Math.pow(1 + monthlyRate, numPayments)) / 
                        (Math.pow(1 + monthlyRate, numPayments) - 1);
    } else {
        monthlyPayment = principal / numPayments;
    }
    
    const formattedPayment = isNaN(monthlyPayment) || monthlyPayment <= 0 ? 
                            '$0' : 
                            '$' + Math.round(monthlyPayment).toLocaleString();
    
    document.getElementById('monthlyPayment').textContent = formattedPayment;
}

// Form submission handler
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.querySelector('form[action="contact_handler.php"]');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Thank you for your interest! An agent will contact you soon.');
            this.reset();
        });
    }
});

// Share functionality
document.addEventListener('DOMContentLoaded', function() {
    const shareButtons = document.querySelectorAll('.property-info-card button');
    shareButtons.forEach(button => {
        if (button.innerHTML.includes('fa-facebook') || 
            button.innerHTML.includes('fa-twitter') || 
            button.innerHTML.includes('fa-whatsapp') || 
            button.innerHTML.includes('fa-envelope')) {
            button.addEventListener('click', function() {
                const url = window.location.href;
                const title = document.title;
                
                if (this.innerHTML.includes('fa-facebook')) {
                    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank');
                } else if (this.innerHTML.includes('fa-twitter')) {
                    window.open(`https://twitter.com/intent/tweet?url=${url}&text=${title}`, '_blank');
                } else if (this.innerHTML.includes('fa-whatsapp')) {
                    window.open(`https://wa.me/?text=${title} ${url}`, '_blank');
                } else if (this.innerHTML.includes('fa-envelope')) {
                    window.location.href = `mailto:?subject=${title}&body=Check out this property: ${url}`;
                }
            });
        }
    });
});
</script>

<?php include "footer.php"; ?>