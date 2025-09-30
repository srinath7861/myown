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
    /* Mobile-First Gallery Styles */
    .gallery-container {
        position: relative;
        overflow: hidden;
        border-radius: 0.75rem;
    }
    
    .gallery-main {
        position: relative;
        width: 100%;
        padding-bottom: 75%; /* 4:3 Aspect Ratio */
        background: #f3f4f6;
    }
    
    @media (min-width: 768px) {
        .gallery-main {
            padding-bottom: 56.25%; /* 16:9 Aspect Ratio on larger screens */
        }
    }
    
    .gallery-image {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    /* Thumbnail Grid */
    .thumbnail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        gap: 0.5rem;
        margin-top: 0.5rem;
    }
    
    @media (min-width: 640px) {
        .thumbnail-grid {
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 0.75rem;
            margin-top: 0.75rem;
        }
    }
    
    .thumbnail-item {
        position: relative;
        padding-bottom: 75%;
        background: #e5e7eb;
        border-radius: 0.5rem;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .thumbnail-item:hover {
        transform: scale(1.05);
    }
    
    .thumbnail-item.active {
        box-shadow: 0 0 0 3px #ddcabc;
    }
    
    .thumbnail-image {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    /* Lightbox Styles */
    .lightbox {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.95);
        z-index: 9999;
        animation: fadeIn 0.3s ease;
    }
    
    .lightbox.active {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .lightbox-content {
        position: relative;
        max-width: 95%;
        max-height: 95%;
    }
    
    .lightbox-image {
        max-width: 100%;
        max-height: 90vh;
        object-fit: contain;
    }
    
    .lightbox-close {
        position: absolute;
        top: 20px;
        right: 20px;
        color: white;
        font-size: 2rem;
        cursor: pointer;
        z-index: 10000;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.5);
        border-radius: 50%;
    }
    
    .lightbox-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 1rem;
        background: rgba(0, 0, 0, 0.5);
        border-radius: 0.5rem;
        transition: background 0.3s ease;
    }
    
    .lightbox-nav:hover {
        background: rgba(0, 0, 0, 0.8);
    }
    
    .lightbox-prev {
        left: 20px;
    }
    
    .lightbox-next {
        right: 20px;
    }
    
    .lightbox-counter {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        color: white;
        background: rgba(0, 0, 0, 0.7);
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        font-size: 0.875rem;
    }
    
    /* Tab Styles for Mobile */
    .tab-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    
    .tab-container::-webkit-scrollbar {
        display: none;
    }
    
    .tab-nav {
        display: flex;
        min-width: max-content;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .tab-btn {
        padding: 0.75rem 1.5rem;
        font-weight: 500;
        color: #6b7280;
        border-bottom: 2px solid transparent;
        transition: all 0.3s ease;
        white-space: nowrap;
    }
    
    .tab-btn.active {
        color: #c8b5a5;
        border-bottom-color: #c8b5a5;
    }
    
    .tab-content {
        display: none;
        animation: fadeIn 0.3s ease;
    }
    
    .tab-content.active {
        display: block;
    }
    
    /* Mobile Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    @media (min-width: 640px) {
        .stats-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
    }
    
    @media (min-width: 1024px) {
        .stats-grid {
            grid-template-columns: repeat(6, 1fr);
        }
    }
    
    /* Animation */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    /* Touch gesture indicators */
    .swipe-hint {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        font-size: 0.75rem;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 0.7; }
        50% { opacity: 1; }
    }
    
    /* Mobile-optimized form inputs */
    .mobile-input {
        font-size: 16px; /* Prevents zoom on iOS */
    }
    
    /* Sticky mobile CTA */
    .mobile-cta {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        border-top: 1px solid #e5e7eb;
        padding: 1rem;
        z-index: 40;
        display: flex;
        gap: 0.5rem;
    }
    
    @media (min-width: 1024px) {
        .mobile-cta {
            display: none;
        }
    }
    
    .no-image-placeholder {
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        color: #6b7280;
        text-align: center;
    }
</style>

<!-- Main Content -->
<main class="container mx-auto px-4 sm:px-6 py-4 sm:py-8 mt-16 pb-24 lg:pb-8">
    
    <!-- Mobile Header -->
    <div class="lg:hidden mb-4">
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
            $city_state_zip = '';
        }
        ?>
        <h1 class="text-2xl font-bold text-black mb-1"><?php echo $street_address; ?></h1>
        <p class="text-gray-600 mb-2"><?php echo $city_state_zip; ?></p>
        <div class="text-2xl font-bold text-cream-dark">$<?php echo number_format($property['ListPrice'] ?? 0); ?></div>
    </div>

    <!-- Property Details Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 lg:gap-8">
        
        <!-- Main Content Area -->
        <div class="lg:col-span-3">
            
            <!-- Image Gallery Section -->
            <div class="gallery-container mb-6">
                <?php if (!empty($propertyImages)): ?>
                    <!-- Main Gallery Image -->
                    <div class="gallery-main">
                        <img id="mainGalleryImage" 
                             src="<?php echo htmlspecialchars($propertyImages[0]['url']); ?>" 
                             alt="<?php echo htmlspecialchars($propertyImages[0]['description']); ?>"
                             class="gallery-image cursor-pointer"
                             onclick="openLightbox(0)">
                        
                        <!-- Image Counter for Mobile -->
                        <div class="absolute bottom-4 right-4 bg-black bg-opacity-60 text-white px-3 py-1 rounded-full text-sm">
                            <span id="imageCounter">1 / <?php echo count($propertyImages); ?></span>
                        </div>
                        
                        <!-- Swipe Hint (shows briefly on mobile) -->
                        <div class="swipe-hint lg:hidden" id="swipeHint">
                            <i class="fas fa-hand-point-up mr-2"></i>Tap to view gallery
                        </div>
                    </div>
                    
                    <!-- Thumbnail Grid -->
                    <?php if (count($propertyImages) > 1): ?>
                    <div class="thumbnail-grid">
                        <?php foreach ($propertyImages as $index => $image): ?>
                        <div class="thumbnail-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                             onclick="changeMainImage(<?php echo $index; ?>)">
                            <img src="<?php echo htmlspecialchars($image['url']); ?>" 
                                 alt="<?php echo htmlspecialchars($image['description']); ?>"
                                 class="thumbnail-image">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <!-- No Images Placeholder -->
                    <div class="gallery-main no-image-placeholder">
                        <div class="p-8">
                            <i class="fas fa-image text-5xl mb-4"></i>
                            <h3 class="text-xl font-semibold mb-2">No Images Available</h3>
                            <p class="text-gray-500 text-sm">Property images are not currently available</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Desktop Header (Hidden on Mobile) -->
            <div class="hidden lg:block mb-8">
                <div class="flex items-start justify-between">
                    <div>
                        <h1 class="text-3xl xl:text-4xl font-bold text-black mb-2"><?php echo $street_address; ?></h1>
                        <p class="text-xl text-gray-600 mb-4"><?php echo $city_state_zip; ?></p>
                        <div class="flex items-center space-x-4 text-sm text-gray-500">
                            <span class="flex items-center">
                                <i class="fas fa-calendar-alt mr-1"></i>
                                On Market <?php echo $property['DaysOnMarket'] ?? 'N/A'; ?> days
                            </span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-4xl xl:text-5xl font-bold text-cream-dark mb-2">
                            $<?php echo number_format($property['ListPrice'] ?? 0); ?>
                        </div>
                        <div class="text-sm text-gray-500">
                            <?php 
                            $price = $property['ListPrice'] ?? 0;
                            $sqft = $property['LivingArea'] ?? 0;
                            
                            if ($price > 0 && $sqft > 0) {
                                echo '$' . number_format($price / $sqft) . ' per sq ft';
                            } else {
                                echo 'Price per sq ft N/A';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Key Stats -->
            <div class="bg-cream bg-opacity-20 rounded-lg p-4 sm:p-6 mb-6">
                <div class="stats-grid">
                    <div class="text-center bg-white rounded-lg p-3 sm:p-4 shadow-sm">
                        <i class="fas fa-bed text-xl sm:text-2xl text-cream-dark mb-2"></i>
                        <div class="text-lg sm:text-xl font-bold"><?php echo $property['BedroomsTotal'] ?? 'N/A'; ?></div>
                        <div class="text-xs sm:text-sm text-gray-600">Beds</div>
                    </div>
                    <div class="text-center bg-white rounded-lg p-3 sm:p-4 shadow-sm">
                        <i class="fas fa-bath text-xl sm:text-2xl text-cream-dark mb-2"></i>
                        <div class="text-lg sm:text-xl font-bold">
                            <?php echo $property['BathroomsTotalInteger'] ?? $property['BathroomsTotalDecimal'] ?? 'N/A'; ?>
                        </div>
                        <div class="text-xs sm:text-sm text-gray-600">Baths</div>
                    </div>
                    <div class="text-center bg-white rounded-lg p-3 sm:p-4 shadow-sm">
                        <i class="fas fa-expand-arrows-alt text-xl sm:text-2xl text-cream-dark mb-2"></i>
                        <div class="text-lg sm:text-xl font-bold">
                            <?php $sqft = $property['LivingArea'] ?? 0; echo $sqft > 0 ? number_format($sqft) : 'N/A'; ?>
                        </div>
                        <div class="text-xs sm:text-sm text-gray-600">Sq Ft</div>
                    </div>
                    <div class="text-center bg-white rounded-lg p-3 sm:p-4 shadow-sm">
                        <i class="fas fa-car text-xl sm:text-2xl text-cream-dark mb-2"></i>
                        <div class="text-lg sm:text-xl font-bold">
                            <?php echo $property['ParkingTotal'] ?? $property['GarageSpaces'] ?? 'N/A'; ?>
                        </div>
                        <div class="text-xs sm:text-sm text-gray-600">Parking</div>
                    </div>
                    <div class="text-center bg-white rounded-lg p-3 sm:p-4 shadow-sm">
                        <i class="fas fa-calendar text-xl sm:text-2xl text-cream-dark mb-2"></i>
                        <div class="text-lg sm:text-xl font-bold"><?php echo $property['YearBuilt'] ?? 'N/A'; ?></div>
                        <div class="text-xs sm:text-sm text-gray-600">Built</div>
                    </div>
                    <div class="text-center bg-white rounded-lg p-3 sm:p-4 shadow-sm">
                        <i class="fas fa-chart-area text-xl sm:text-2xl text-cream-dark mb-2"></i>
                        <div class="text-lg sm:text-xl font-bold">
                            <?php echo $property['LotSizeAcres'] ?? 'N/A'; ?>
                        </div>
                        <div class="text-xs sm:text-sm text-gray-600">Acres</div>
                    </div>
                </div>
            </div>
            
            <!-- Tabs Section -->
            <div class="mb-8">
                <div class="tab-container">
                    <nav class="tab-nav">
                        <button class="tab-btn active" data-tab="overview">Overview</button>
                        <button class="tab-btn" data-tab="details">Details</button>
                        <button class="tab-btn" data-tab="features">Features</button>
                    </nav>
                </div>
                
                <!-- Overview Tab -->
                <div id="overview" class="tab-content active">
                    <div class="bg-cream bg-opacity-20 rounded-lg p-4 sm:p-6 mt-4">
                        <h3 class="text-xl sm:text-2xl font-bold text-black mb-4">Property Description</h3>
                        <p class="text-gray-700 leading-relaxed text-sm sm:text-base">
                            <?php 
                            $description = $property['PublicRemarks'] ?? $property['SyndicationRemarks'] ?? '';
                            if (empty($description)) {
                                $bedrooms = $property['BedroomsTotal'] ?? 'multiple';
                                $bathrooms = $property['BathroomsTotalInteger'] ?? $property['BathroomsTotalDecimal'] ?? 'multiple';
                                $sqft = $property['LivingArea'] ?? 0;
                                $city = $property['City'] ?? 'a desirable area';
                                
                                $description = "Discover this exceptional property featuring {$bedrooms} bedrooms and {$bathrooms} bathrooms";
                                if ($sqft > 0) {
                                    $description .= " across " . number_format($sqft) . " square feet of living space";
                                }
                                $description .= ". Located in {$city}, this property offers excellent value and potential.";
                            }
                            echo htmlspecialchars($description); 
                            ?>
                        </p>
                        
                        <!-- Quick Facts Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-3">Property Info</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">MLS ID:</span>
                                        <span>#<?php echo $property['ListingId'] ?? 'N/A'; ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Status:</span>
                                        <span class="<?php echo ($property['MlsStatus'] ?? 'N/A') === 'Active' ? 'text-green-600 font-medium' : 'text-red-600 font-medium'; ?>">
                                            <?php echo $property['MlsStatus'] ?? 'N/A'; ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Type:</span>
                                        <span><?php echo $property['PropertyType'] ?? 'N/A'; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-3">Financial</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Tax:</span>
                                        <span>$<?php echo isset($property['TaxAnnualAmount']) ? number_format($property['TaxAnnualAmount']) : 'N/A'; ?>/yr</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">HOA:</span>
                                        <span>
                                            <?php 
                                            if (isset($property['AssociationFee'])) {
                                                echo '$' . number_format($property['AssociationFee']) . '/' . ($property['AssociationFeeFrequency'] ?? 'mo');
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-3">Amenities</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Waterfront:</span>
                                        <span><?php echo isset($property['WaterfrontYN']) && $property['WaterfrontYN'] ? 'Yes' : 'No'; ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Pool:</span>
                                        <span><?php echo isset($property['PoolPrivateYN']) && $property['PoolPrivateYN'] ? 'Yes' : 'No'; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Details Tab -->
                <div id="details" class="tab-content">
                    <div class="bg-cream bg-opacity-20 rounded-lg p-4 sm:p-6 mt-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="text-lg font-bold mb-4">Interior</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between py-2 border-b border-cream">
                                        <span class="text-gray-600">Bedrooms:</span>
                                        <span><?php echo $property['BedroomsTotal'] ?? 'N/A'; ?></span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-cream">
                                        <span class="text-gray-600">Bathrooms:</span>
                                        <span><?php echo $property['BathroomsTotalInteger'] ?? $property['BathroomsTotalDecimal'] ?? 'N/A'; ?></span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-cream">
                                        <span class="text-gray-600">Living Area:</span>
                                        <span><?php echo isset($property['LivingArea']) ? number_format($property['LivingArea']) . ' sq ft' : 'N/A'; ?></span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-cream">
                                        <span class="text-gray-600">Stories:</span>
                                        <span><?php echo $property['StoriesTotal'] ?? $property['Stories'] ?? 'N/A'; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h4 class="text-lg font-bold mb-4">Exterior</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between py-2 border-b border-cream">
                                        <span class="text-gray-600">Lot Size:</span>
                                        <span><?php echo isset($property['LotSizeAcres']) ? $property['LotSizeAcres'] . ' acres' : 'N/A'; ?></span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-cream">
                                        <span class="text-gray-600">Parking:</span>
                                        <span><?php echo $property['ParkingTotal'] ?? $property['GarageSpaces'] ?? 'N/A'; ?> spaces</span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-cream">
                                        <span class="text-gray-600">Year Built:</span>
                                        <span><?php echo $property['YearBuilt'] ?? 'N/A'; ?></span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-cream">
                                        <span class="text-gray-600">Pool:</span>
                                        <span><?php echo isset($property['PoolPrivateYN']) && $property['PoolPrivateYN'] ? 'Private Pool' : 'No'; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Features Tab -->
                <div id="features" class="tab-content">
                    <div class="bg-cream bg-opacity-20 rounded-lg p-4 sm:p-6 mt-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- Interior Features -->
                            <div class="bg-white rounded-lg p-4">
                                <h4 class="font-bold text-base mb-3">
                                    <i class="fas fa-home mr-2 text-cream-dark"></i>Interior
                                </h4>
                                <ul class="space-y-1 text-sm text-gray-600">
                                    <?php 
                                    if (isset($property['InteriorFeatures']) && is_array($property['InteriorFeatures'])) {
                                        foreach (array_slice($property['InteriorFeatures'], 0, 5) as $feature) {
                                            echo '<li>• ' . htmlspecialchars($feature) . '</li>';
                                        }
                                    } else {
                                        echo '<li>• Modern interior</li>';
                                        echo '<li>• Updated fixtures</li>';
                                    }
                                    ?>
                                </ul>
                            </div>
                            
                            <!-- Exterior Features -->
                            <div class="bg-white rounded-lg p-4">
                                <h4 class="font-bold text-base mb-3">
                                    <i class="fas fa-tree mr-2 text-cream-dark"></i>Exterior
                                </h4>
                                <ul class="space-y-1 text-sm text-gray-600">
                                    <?php 
                                    if (isset($property['ExteriorFeatures']) && is_array($property['ExteriorFeatures'])) {
                                        foreach (array_slice($property['ExteriorFeatures'], 0, 5) as $feature) {
                                            echo '<li>• ' . htmlspecialchars($feature) . '</li>';
                                        }
                                    } else {
                                        echo '<li>• Well-maintained exterior</li>';
                                        echo '<li>• Landscaped yard</li>';
                                    }
                                    ?>
                                </ul>
                            </div>
                            
                            <!-- Utilities -->
                            <div class="bg-white rounded-lg p-4">
                                <h4 class="font-bold text-base mb-3">
                                    <i class="fas fa-cog mr-2 text-cream-dark"></i>Utilities
                                </h4>
                                <ul class="space-y-1 text-sm text-gray-600">
                                    <li>• <?php echo isset($property['CoolingYN']) && $property['CoolingYN'] ? 'Central Air' : 'Cooling System'; ?></li>
                                    <li>• <?php echo isset($property['HeatingYN']) && $property['HeatingYN'] ? 'Central Heat' : 'Heating System'; ?></li>
                                    <li>• Public utilities</li>
                                    <li>• High-speed internet ready</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Virtual Tour Section (Mobile Optimized) -->
            <section class="bg-cream bg-opacity-20 rounded-lg p-4 sm:p-6 mb-6">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4">Virtual Experience</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-gradient-to-br from-cream to-black rounded-lg p-4 sm:p-6 text-white text-center">
                        <i class="fas fa-vr-cardboard text-3xl sm:text-4xl mb-3"></i>
                        <h3 class="text-lg sm:text-xl font-bold mb-2">3D Tour</h3>
                        <p class="text-sm mb-3">Immersive walkthrough</p>
                        <button class="bg-white text-black px-4 py-2 rounded text-sm font-semibold hover:bg-gray-100 transition-colors w-full sm:w-auto"
                                onclick="window.open('<?php echo $property['VirtualTourURLUnbranded'] ?? '#'; ?>', '_blank')">
                            Start Tour
                        </button>
                    </div>
                    <div class="bg-gradient-to-br from-black to-cream rounded-lg p-4 sm:p-6 text-white text-center">
                        <i class="fas fa-video text-3xl sm:text-4xl mb-3"></i>
                        <h3 class="text-lg sm:text-xl font-bold mb-2">Video Tour</h3>
                        <p class="text-sm mb-3">Guided walkthrough</p>
                        <button class="bg-white text-black px-4 py-2 rounded text-sm font-semibold hover:bg-gray-100 transition-colors w-full sm:w-auto">
                            Watch Video
                        </button>
                    </div>
                </div>
            </section>
        </div>
        
        <!-- Sidebar (Desktop) / Bottom Section (Mobile) -->
        <div class="lg:col-span-1 space-y-4">
            
            <!-- Contact Agent Card -->
            <div class="bg-cream bg-opacity-20 rounded-lg p-4 sm:p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Contact Agent</h3>
                
                <!-- Agent Info -->
                <div class="flex items-center mb-4 p-3 bg-gray-50 rounded-lg">
                    <div class="w-12 h-12 bg-cream rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-user text-xl text-cream-dark"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-bold text-sm sm:text-base">
                            <?php echo htmlspecialchars($property['ListAgentFullName'] ?? 'Real Estate Agent'); ?>
                        </div>
                        <div class="text-xs sm:text-sm text-gray-600">
                            <?php echo htmlspecialchars($property['ListOfficeName'] ?? 'Real Estate Office'); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <form method="POST" action="contact_handler.php" class="space-y-3">
                    <input type="hidden" name="property_id" value="<?php echo htmlspecialchars($property['ListingKey']); ?>">
                    
                    <input type="text" name="name" placeholder="Your Name" required
                           class="mobile-input w-full px-3 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-cream-dark text-sm">
                    
                    <input type="email" name="email" placeholder="Your Email" required
                           class="mobile-input w-full px-3 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-cream-dark text-sm">
                    
                    <input type="tel" name="phone" placeholder="Your Phone"
                           class="mobile-input w-full px-3 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-cream-dark text-sm">
                    
                    <select name="interest" class="mobile-input w-full px-3 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-cream-dark text-sm">
                        <option>I'm interested in this property</option>
                        <option>Schedule a showing</option>
                        <option>Request more information</option>
                        <option>Get financing options</option>
                    </select>
                    
                    <textarea name="message" placeholder="Message (Optional)" rows="3"
                              class="mobile-input w-full px-3 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-cream-dark resize-none text-sm"></textarea>
                    
                    <button type="submit" class="w-full bg-black text-white font-bold py-3 px-4 rounded hover:bg-gray-800 transition-colors text-sm">
                        Send Message
                    </button>
                </form>
                
                <!-- Quick Contact -->
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="text-xs text-gray-600 mb-2">Or contact directly:</div>
                    <div class="flex gap-2">
                        <button class="flex-1 bg-green-600 text-white py-2 px-3 rounded text-xs hover:bg-green-700 transition-colors">
                            <i class="fab fa-whatsapp mr-1"></i>WhatsApp
                        </button>
                        <button class="flex-1 bg-blue-600 text-white py-2 px-3 rounded text-xs hover:bg-blue-700 transition-colors">
                            <i class="fas fa-phone mr-1"></i>Call
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Mortgage Calculator -->
            <div class="bg-cream bg-opacity-20 rounded-lg p-4 sm:p-6">
                <h3 class="text-lg font-bold mb-4">Mortgage Calculator</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium mb-1">Home Value</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-sm">$</span>
                            <input type="number" id="homeValue" value="<?php echo $property['ListPrice'] ?? 0; ?>"
                                   class="mobile-input w-full pl-8 pr-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-cream text-sm">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-medium mb-1">Down Payment</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-sm">$</span>
                            <input type="number" id="downPayment" value="<?php echo round(($property['ListPrice'] ?? 0) * 0.2); ?>"
                                   class="mobile-input w-full pl-8 pr-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-cream text-sm">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-medium mb-1">Interest Rate</label>
                        <div class="relative">
                            <input type="number" id="interestRate" value="7.5" step="0.1"
                                   class="mobile-input w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-cream text-sm">
                            <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-sm">%</span>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-medium mb-1">Loan Term</label>
                        <select id="loanTerm" class="mobile-input w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-cream text-sm">
                            <option value="30">30 years</option>
                            <option value="20">20 years</option>
                            <option value="15">15 years</option>
                        </select>
                    </div>
                    
                    <button onclick="calculateMortgage()" class="w-full bg-cream-dark text-white py-2 rounded hover:bg-cream transition font-medium text-sm">
                        Calculate
                    </button>
                </div>
                
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="text-xs font-medium mb-1">Monthly Payment</div>
                    <div id="monthlyPayment" class="text-2xl font-bold text-cream-dark">$0</div>
                    <p class="text-xs text-gray-600 mt-1">Principal & Interest only</p>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Mobile Sticky CTA Buttons -->
<div class="mobile-cta">
    <button onclick="document.getElementById('contactForm').scrollIntoView({behavior: 'smooth'})" 
            class="flex-1 bg-black text-white font-bold py-3 px-4 rounded text-sm">
        <i class="fas fa-envelope mr-2"></i>Contact
    </button>
    <button onclick="window.location.href='tel:<?php echo $property['ListAgentDirectPhone'] ?? '1800000000'; ?>'" 
            class="flex-1 bg-cream text-black font-bold py-3 px-4 rounded text-sm">
        <i class="fas fa-phone mr-2"></i>Call
    </button>
</div>

<!-- Lightbox Gallery -->
<?php if (!empty($propertyImages)): ?>
<div id="lightbox" class="lightbox">
    <span class="lightbox-close" onclick="closeLightbox()">
        <i class="fas fa-times"></i>
    </span>
    
    <div class="lightbox-content">
        <img id="lightboxImage" src="" alt="" class="lightbox-image">
        
        <?php if (count($propertyImages) > 1): ?>
        <button class="lightbox-nav lightbox-prev" onclick="navigateLightbox(-1)">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="lightbox-nav lightbox-next" onclick="navigateLightbox(1)">
            <i class="fas fa-chevron-right"></i>
        </button>
        <div class="lightbox-counter">
            <span id="lightboxCounter">1 / <?php echo count($propertyImages); ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
<?php if (!empty($propertyImages)): ?>
// Property images data
const propertyImages = <?php echo json_encode($propertyImages); ?>;
let currentImageIndex = 0;
let touchStartX = 0;
let touchEndX = 0;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    setupTabs();
    setupGallery();
    calculateMortgage();
    hideSwipeHint();
});

// Hide swipe hint after a few seconds
function hideSwipeHint() {
    const hint = document.getElementById('swipeHint');
    if (hint) {
        setTimeout(() => {
            hint.style.display = 'none';
        }, 3000);
    }
}

// Gallery Functions
function changeMainImage(index) {
    currentImageIndex = index;
    const mainImage = document.getElementById('mainGalleryImage');
    const counter = document.getElementById('imageCounter');
    const thumbnails = document.querySelectorAll('.thumbnail-item');
    
    if (mainImage && propertyImages[index]) {
        mainImage.src = propertyImages[index].url;
        mainImage.alt = propertyImages[index].description;
        
        if (counter) {
            counter.textContent = `${index + 1} / ${propertyImages.length}`;
        }
        
        // Update thumbnail active state
        thumbnails.forEach((thumb, i) => {
            if (i === index) {
                thumb.classList.add('active');
            } else {
                thumb.classList.remove('active');
            }
        });
    }
}

// Lightbox Functions
function openLightbox(index) {
    currentImageIndex = index;
    const lightbox = document.getElementById('lightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxCounter = document.getElementById('lightboxCounter');
    
    if (lightbox && lightboxImage && propertyImages[index]) {
        lightboxImage.src = propertyImages[index].url;
        lightboxImage.alt = propertyImages[index].description;
        lightbox.classList.add('active');
        
        if (lightboxCounter) {
            lightboxCounter.textContent = `${index + 1} / ${propertyImages.length}`;
        }
        
        // Prevent body scroll when lightbox is open
        document.body.style.overflow = 'hidden';
    }
}

function closeLightbox() {
    const lightbox = document.getElementById('lightbox');
    if (lightbox) {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function navigateLightbox(direction) {
    let newIndex = currentImageIndex + direction;
    
    // Loop around
    if (newIndex < 0) {
        newIndex = propertyImages.length - 1;
    } else if (newIndex >= propertyImages.length) {
        newIndex = 0;
    }
    
    openLightbox(newIndex);
    changeMainImage(newIndex);
}

// Touch/Swipe Support for Gallery
function setupGallery() {
    const mainImage = document.getElementById('mainGalleryImage');
    const lightboxImage = document.getElementById('lightboxImage');
    
    // Add swipe support to main gallery image
    if (mainImage) {
        mainImage.addEventListener('touchstart', handleTouchStart, {passive: true});
        mainImage.addEventListener('touchend', handleTouchEnd, {passive: true});
    }
    
    // Add swipe support to lightbox
    if (lightboxImage) {
        lightboxImage.addEventListener('touchstart', handleTouchStart, {passive: true});
        lightboxImage.addEventListener('touchend', handleTouchEnd, {passive: true});
    }
    
    // Keyboard navigation for lightbox
    document.addEventListener('keydown', function(e) {
        const lightbox = document.getElementById('lightbox');
        if (lightbox && lightbox.classList.contains('active')) {
            if (e.key === 'ArrowLeft') {
                navigateLightbox(-1);
            } else if (e.key === 'ArrowRight') {
                navigateLightbox(1);
            } else if (e.key === 'Escape') {
                closeLightbox();
            }
        }
    });
    
    // Close lightbox on background click
    const lightbox = document.getElementById('lightbox');
    if (lightbox) {
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox) {
                closeLightbox();
            }
        });
    }
}

function handleTouchStart(e) {
    touchStartX = e.changedTouches[0].screenX;
}

function handleTouchEnd(e) {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
}

function handleSwipe() {
    const swipeThreshold = 50;
    const diff = touchStartX - touchEndX;
    
    if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0) {
            // Swiped left - next image
            navigateGallery(1);
        } else {
            // Swiped right - previous image
            navigateGallery(-1);
        }
    }
}

function navigateGallery(direction) {
    let newIndex = currentImageIndex + direction;
    
    if (newIndex < 0) {
        newIndex = propertyImages.length - 1;
    } else if (newIndex >= propertyImages.length) {
        newIndex = 0;
    }
    
    changeMainImage(newIndex);
    
    // If lightbox is open, update it too
    const lightbox = document.getElementById('lightbox');
    if (lightbox && lightbox.classList.contains('active')) {
        openLightbox(newIndex);
    }
}

<?php else: ?>
// No images - just initialize calculator
document.addEventListener('DOMContentLoaded', function() {
    setupTabs();
    calculateMortgage();
});
<?php endif; ?>

// Tab Navigation
function setupTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabId = button.getAttribute('data-tab');
            
            // Update button states
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            button.classList.add('active');
            
            // Update content visibility
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            const activeContent = document.getElementById(tabId);
            if (activeContent) {
                activeContent.classList.add('active');
            }
        });
    });
}

// Mortgage Calculator
function calculateMortgage() {
    const homeValue = parseFloat(document.getElementById('homeValue').value) || 0;
    const downPayment = parseFloat(document.getElementById('downPayment').value) || 0;
    const loanTerm = parseInt(document.getElementById('loanTerm').value) || 30;
    const interestRate = parseFloat(document.getElementById('interestRate').value) || 0;
    
    const loanAmount = homeValue - downPayment;
    const monthlyInterestRate = interestRate / 100 / 12;
    const numberOfPayments = loanTerm * 12;
    
    let monthlyPayment = 0;
    
    if (monthlyInterestRate === 0) {
        monthlyPayment = loanAmount / numberOfPayments;
    } else {
        monthlyPayment = loanAmount * (monthlyInterestRate * Math.pow(1 + monthlyInterestRate, numberOfPayments)) / 
                         (Math.pow(1 + monthlyInterestRate, numberOfPayments) - 1);
    }
    
    const formattedPayment = isNaN(monthlyPayment) || monthlyPayment <= 0 ? 
                            '$0' : 
                            '$' + monthlyPayment.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    
    document.getElementById('monthlyPayment').textContent = formattedPayment;
}

// Form submission handler
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.querySelector('form[action="contact_handler.php"]');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Thank you for your interest! An agent will contact you soon.');
            contactForm.reset();
        });
    }
});

// Smooth scroll for mobile CTA
function smoothScroll(targetId) {
    const element = document.getElementById(targetId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}
</script>

<?php include "footer.php"; ?>