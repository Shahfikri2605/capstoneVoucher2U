<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Start the session to access $_SESSION['Id']

require_once '../../databaseConnection/db_config.php';

// Option 1: Use Composer autoloader (recommended)
require_once '../../vendor/autoload.php';

// If using autoloader, keep the use statement
use TCPDF;
use chillerlan\QRCode\{QRCode, QROptions};
use chillerlan\QRCode\Data\QRMatrix;

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    $input = json_decode(file_get_contents('php://input'), true);
    $redeemedVouchers = $input['redeemedVouchers'] ?? [];
    $newPointsBalance = $input['newPointsBalance'] ?? 0;

    if (empty($redeemedVouchers)) {
        $response['message'] = 'No vouchers to generate PDF for.';
        echo json_encode($response);
        exit;
    }

    try {
        // Debug: Log the received voucher data
        error_log("Received voucher data: " . print_r($redeemedVouchers, true));
        
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Voucher2U - OptimaBank');
        $pdf->SetAuthor('OptimaBank');
        $pdf->SetTitle('Redeemed Vouchers');
        $pdf->SetSubject('Voucher Redemption Receipt');

        // Set default header/footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);

        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 25);

        // Set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', 'B', 16);

        // Header
        $pdf->Cell(0, 10, 'VOUCHER REDEMPTION RECEIPT', 0, 1, 'C');
        $pdf->Ln(5);

        // Company info
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'OptimaBank - Voucher2U', 0, 1, 'C');
        $pdf->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $pdf->Cell(0, 5, 'Remaining Points Balance: ' . $newPointsBalance, 0, 1, 'C');
        $pdf->Ln(10);

        // Enhanced function to get voucher image with hybrid approach
        function getVoucherImagePath($pdo, $voucherId, $voucherTitle, $existingImagePath = null) {
            $imagePaths = [];
            
            // Get the absolute path to the project root
            $projectRoot = dirname(dirname(__FILE__));
            
            error_log("=== IMAGE DEBUG START ===");
            error_log("Project root: " . $projectRoot);
            error_log("Voucher ID: " . ($voucherId ?? 'NULL'));
            error_log("Voucher Title: " . ($voucherTitle ?? 'NULL'));
            error_log("Existing Image Path: " . ($existingImagePath ?? 'NULL'));
            
            try {
                // Method 1: Get image name from database if we have voucher ID
                if ($voucherId) {
                    $stmt = $pdo->prepare("SELECT Title, Image FROM Voucher WHERE Id = ?");
                    $stmt->execute([$voucherId]);
                    $dbResult = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $dbTitle = $dbResult['Title'] ?? null;
                    $dbImageName = $dbResult['Image'] ?? null;
                    
                    error_log("Database Title: " . ($dbTitle ?? 'NULL'));
                    error_log("Database Image: " . ($dbImageName ?? 'NULL'));
                    
                    // Try database image first if it exists
                    if (!empty($dbImageName)) {
                        $imagePaths[] = $projectRoot . '/assets/voucher_images/' . $dbImageName;
                        $imagePaths[] = $projectRoot . '/assets/voucher_images/' . basename($dbImageName);
                        
                        // Try with different extensions if no extension found
                        $pathInfo = pathinfo($dbImageName);
                        if (empty($pathInfo['extension'])) {
                            $imagePaths[] = $projectRoot . '/assets/voucher_images/' . $dbImageName . '.jpg';
                            $imagePaths[] = $projectRoot . '/assets/voucher_images/' . $dbImageName . '.png';
                            $imagePaths[] = $projectRoot . '/assets/voucher_images/' . $dbImageName . '.jpeg';
                        }
                    }
                    
                    // Use database title if available for mapping
                    $titleForMapping = !empty($dbTitle) ? $dbTitle : $voucherTitle;
                    if (!empty($titleForMapping)) {
                        $mappedFileName = mapTitleToImageFileName($titleForMapping);
                        if ($mappedFileName) {
                            $imagePaths[] = $projectRoot . '/assets/voucher_images/' . $mappedFileName;
                            error_log("Mapped filename from title: " . $mappedFileName);
                        }
                    }
                }
                
                // Method 2: Process existing image path from cart data (ENHANCED)
                if (!empty($existingImagePath)) {
                    error_log("Processing existing image path: " . $existingImagePath);
                    
                    // Clean and extract filename
                    $filename = basename($existingImagePath);
                    $imagePaths[] = $projectRoot . '/assets/voucher_images/' . $filename;
                    
                    // Handle relative paths like "../assets/voucher_images/fast-food-voucher.jpg"
                    $cleanPaths = [
                        str_replace('../assets/voucher_images/', '', $existingImagePath),
                        str_replace('assets/voucher_images/', '', $existingImagePath),
                        str_replace('./assets/voucher_images/', '', $existingImagePath),
                        str_replace('/assets/voucher_images/', '', $existingImagePath)
                    ];
                    
                    foreach ($cleanPaths as $cleanPath) {
                        if ($cleanPath !== $existingImagePath && !empty($cleanPath)) {
                            $imagePaths[] = $projectRoot . '/assets/voucher_images/' . $cleanPath;
                            $imagePaths[] = $projectRoot . '/assets/voucher_images/' . basename($cleanPath);
                        }
                    }
                    
                    // Direct processing for specific case
                    if ($existingImagePath === "../assets/voucher_images/fast-food-voucher.jpg") {
                        $imagePaths[] = $projectRoot . '/assets/voucher_images/fast-food-voucher.jpg';
                        error_log("Added direct path for fast-food-voucher.jpg");
                    }
                }
                
                // Method 3: Generate path from title as fallback
                if (!empty($voucherTitle)) {
                    $derivedImageName = strtolower(str_replace(' ', '-', $voucherTitle));
                    $imagePaths[] = $projectRoot . '/assets/voucher_images/' . $derivedImageName . '.jpg';
                    $imagePaths[] = $projectRoot . '/assets/voucher_images/' . $derivedImageName . '.png';
                    $imagePaths[] = $projectRoot . '/assets/voucher_images/' . $derivedImageName . '.jpeg';
                }
                
                // Method 4: Add some additional fallback paths
                if (!empty($existingImagePath) && strpos($existingImagePath, 'home-essentials-voucher') !== false) {
                    $imagePaths[] = $projectRoot . '/assets/voucher_images/home-essentials-voucher.jpg';
                }
                
            } catch (Exception $e) {
                error_log("Error fetching voucher image from database: " . $e->getMessage());
            }
            
            // Remove duplicates
            $imagePaths = array_unique($imagePaths);
            
            error_log("All image paths to try: " . print_r($imagePaths, true));
            error_log("=== IMAGE DEBUG END ===");
            
            return $imagePaths;
        }

        // Function to map voucher titles to specific image filenames
        function mapTitleToImageFileName($title) {
            $titleLower = strtolower(trim($title));
            
            // Define mapping of title keywords to image filenames
            $titleMappings = [
                // More specific multi-word matches first
                'general gift card voucher' => 'gift-card-voucher.jpg',
                'gift card' => 'gift-card-voucher.jpg',
                'fast food' => 'fast-food-voucher.jpg',
                'car rental' => 'car-rental-voucher.jpg',
                'fine dining' => 'fine-dining-voucher.jpg',
                'vacation package' => 'travel-voucher.jpg',
                
                // General categories / single-word matches
                'travel' => 'travel-voucher.jpg',
                'flight' => 'flight-discount-voucher.jpg',
                'vacation' => 'vacation-package-voucher.jpg',
                'trip' => 'travel-voucher.jpg',
                
                'food' => 'fast-food-voucher.jpg',
                'restaurant' => 'restaurant-voucher.jpg',
                'burger' => 'fast-food-voucher.jpg',
                'pizza' => 'fast-food-voucher.jpg',
                'dining' => 'fine-dining-voucher.jpg',
                'gourmet' => 'fine-dining-voucher.jpg',
                'steak' => 'fine-dining-voucher.jpg',

                'hotel' => 'hotel-booking-voucher.jpg',
                'accommodation' => 'hotel-voucher.jpg',
                'booking' => 'hotel-voucher.jpg',
                'stay' => 'hotel-voucher.jpg',
                
                'car' => 'car-rental-voucher.jpg',
                'rental' => 'car-rental-voucher.jpg',
                'vehicle' => 'car-rental-voucher.jpg',
                
                'coffee' => 'coffee-voucher.jpg',
                'cafe' => 'café-voucher.jpg',
                'espresso' => 'coffee-voucher.jpg',
                'latte' => 'coffee-voucher.jpg',
                
                'shopping' => 'shopping-voucher.jpg',
                'retail' => 'shopping-voucher.jpg',
                'store' => 'shopping-voucher.jpg',
                'mall' => 'shopping-voucher.jpg',
                'fashion'=>'fashion-voucher.jpg',
                
                'electronics' => 'electronics-voucher.jpg',
                'gadget' => 'electronics-voucher.jpg',
                'tech' => 'electronics-voucher.jpg',
                
                'fashion' => 'fashion-voucher.jpg',
                'clothing' => 'fashion-voucher.jpg',
                'apparel' => 'fashion-voucher.jpg',
                
                'home' => 'home-essentials-voucher.jpg',
                'furniture' => 'home-essentials-voucher.jpg',
                'essentials' => 'home-essentials-voucher.jpg',
                'household' => 'home-essentials-voucher.jpg',
                
                'entertainment' => 'entertainment-voucher.jpg',
                'fun' => 'entertainment-voucher.jpg',
                'amusement' => 'entertainment-voucher.jpg',
                'cinema' => 'entertainment-voucher.jpg',
                'movie' => 'entertainment-voucher.jpg',
                
                'gift' => 'gift-card-voucher.jpg',
                'amazon' => 'gift-card-voucher.jpg',
                'netflix' => 'gift-card-voucher.jpg',
                'spotify' => 'gift-card-voucher.jpg',
            ];
            
            // Check for exact matches first
            if (isset($titleMappings[$titleLower])) {
                error_log("Exact match found: '{$titleLower}' -> '{$titleMappings[$titleLower]}'");
                return $titleMappings[$titleLower];
            }
            
            // Check for partial matches (contains keyword)
            foreach ($titleMappings as $keyword => $filename) {
                if (strpos($titleLower, $keyword) !== false) {
                    error_log("Partial match found: '{$titleLower}' contains '{$keyword}' -> '{$filename}'");
                    return $filename;
                }
            }
            
            // Default fallback - create filename from title
            $defaultFilename = strtolower(str_replace(' ', '-', $title)) . '-voucher.jpg';
            error_log("No match found, using default: '{$title}' -> '{$defaultFilename}'");
            
            return $defaultFilename;
        }

        // Function to get voucher terms and conditions from database
        function getVoucherTermsAndConditions($pdo, $voucherId) {
            $defaultTerms = "This voucher is valid for redemption as per the merchant's terms";
            
            if (!$voucherId) {
                return $defaultTerms;
            }
            
            try {
                $stmt = $pdo->prepare("SELECT Terms_and_condition FROM Voucher WHERE Id = ?");
                $stmt->execute([$voucherId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $dbTerms = $result['Terms_and_condition'] ?? null;
                
                // Return database terms if available and not empty, otherwise use default
                if (!empty($dbTerms) && trim($dbTerms) !== '') {
                    return trim($dbTerms);
                }
                
            } catch (Exception $e) {
                error_log("Error fetching voucher terms from database: " . $e->getMessage());
            }
            
            return $defaultTerms;
        }

        // Enhanced image loading function with better error handling
        function loadVoucherImage($pdf, $imagePaths, $voucherTitle) {
            $imageLoaded = false;
            
            foreach ($imagePaths as $imagePath) {
                // Clean the path
                $imagePath = str_replace(['\\', '//'], ['/', '/'], $imagePath);
                $realPath = realpath($imagePath);
                
                error_log("Trying: " . $imagePath);
                error_log("Real path: " . ($realPath ?: 'NOT FOUND'));
                error_log("File exists: " . (file_exists($imagePath) ? 'YES' : 'NO'));
                
                if (file_exists($imagePath)) {
                    // Check file permissions
                    if (!is_readable($imagePath)) {
                        error_log("File not readable: " . $imagePath);
                        continue;
                    }
                    
                    // Check file size
                    $fileSize = filesize($imagePath);
                    if ($fileSize === false || $fileSize == 0) {
                        error_log("File is empty or unreadable: " . $imagePath . " (Size: " . $fileSize . ")");
                        continue;
                    }
                    
                    error_log("File size: " . $fileSize . " bytes");
                    
                    try {
                        // Verify it's a valid image
                        $imageInfo = getimagesize($imagePath);
                        if (!$imageInfo) {
                            error_log("Not a valid image file: " . $imagePath);
                            continue;
                        }
                        
                        error_log("Image info: " . print_r($imageInfo, true));
                        
                        $imageWidth = $imageInfo[0];
                        $imageHeight = $imageInfo[1];
                        $mimeType = $imageInfo['mime'];
                        
                        // Check if TCPDF supports this image type
                        $supportedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                        if (!in_array($mimeType, $supportedTypes)) {
                            error_log("Unsupported image type: " . $mimeType . " for file: " . $imagePath);
                            continue;
                        }
                        
                        // Handle WebP conversion if needed
                        if ($mimeType === 'image/webp') {
                            error_log("Converting WebP to JPEG for TCPDF compatibility: " . $imagePath);
                            
                            // Convert WebP to JPEG temporarily
                            if (function_exists('imagecreatefromwebp') && function_exists('imagejpeg')) {
                                $webpImage = imagecreatefromwebp($imagePath);
                                if ($webpImage) {
                                    $tempJpegFile = tempnam(sys_get_temp_dir(), 'webp_convert_') . '.jpg';
                                    if (imagejpeg($webpImage, $tempJpegFile, 90)) {
                                        error_log("WebP converted to JPEG: " . $tempJpegFile);
                                        $imagePath = $tempJpegFile; // Use converted file
                                        $mimeType = 'image/jpeg';
                                        $tcpdfType = 'JPEG';
                                        imagedestroy($webpImage);
                                    } else {
                                        error_log("Failed to convert WebP to JPEG");
                                        imagedestroy($webpImage);
                                        continue;
                                    }
                                } else {
                                    error_log("Failed to create image from WebP file");
                                    continue;
                                }
                            } else {
                                error_log("WebP functions not available - cannot convert");
                                continue;
                            }
                        }
                        
                        // Calculate scaled dimensions (max width 80mm, max height 40mm)
                        $maxWidth = 80;
                        $maxHeight = 40;
                        
                        $widthScale = $maxWidth / $imageWidth;
                        $heightScale = $maxHeight / $imageHeight;
                        $scaleFactor = min($widthScale, $heightScale);
                        
                        $scaledWidth = $imageWidth * $scaleFactor;
                        $scaledHeight = $imageHeight * $scaleFactor;
                        
                        // Center the image
                        $x = (210 - $scaledWidth) / 2; // 210mm is A4 width
                        
                        // Determine image type for TCPDF
                        $tcpdfType = '';
                        switch ($mimeType) {
                            case 'image/jpeg':
                            case 'image/jpg':
                                $tcpdfType = 'JPEG';
                                break;
                            case 'image/png':
                                $tcpdfType = 'PNG';
                                break;
                            case 'image/gif':
                                $tcpdfType = 'GIF';
                                break;
                        }
                        
                        // Add image to PDF
                        $pdf->Image($imagePath, $x, '', $scaledWidth, $scaledHeight, $tcpdfType, '', 'T', false, 300, '', false, false, 1, false, false, false);
                        $pdf->Ln($scaledHeight + 3);
                        
                        error_log("SUCCESS: Image loaded successfully: " . $imagePath);
                        $imageLoaded = true;
                        break;
                        
                    } catch (Exception $imgException) {
                        error_log("Exception adding image " . $imagePath . ": " . $imgException->getMessage());
                        continue;
                    }
                } else {
                    // Check if directory exists
                    $dir = dirname($imagePath);
                    if (!is_dir($dir)) {
                        error_log("Directory does not exist: " . $dir);
                    } elseif (!is_readable($dir)) {
                        error_log("Directory not readable: " . $dir);
                    }
                }
            }
            
            if (!$imageLoaded) {
                // Add a smaller placeholder box
                $pdf->SetFont('helvetica', 'I', 9);
                $pdf->SetFillColor(245, 245, 245);
                $pdf->Rect(65, $pdf->GetY(), 80, 25, 'F');
                $pdf->SetXY(65, $pdf->GetY() + 11);
                $pdf->Cell(80, 4, '[Image Not Available]', 0, 1, 'C');
                $pdf->Ln(18);
                error_log("FAILED: No image found for voucher: " . $voucherTitle);
            }
            
            return $imageLoaded;
        }

        // Helper function to list all files in voucher_images directory (for debugging)
        function debugListImageDirectory($projectRoot) {
            $imageDir = $projectRoot . '/assets/voucher_images/';
            error_log("=== DIRECTORY LISTING DEBUG ===");
            error_log("Image directory: " . $imageDir);
            error_log("Directory exists: " . (is_dir($imageDir) ? 'YES' : 'NO'));
            
            if (is_dir($imageDir)) {
                error_log("Directory readable: " . (is_readable($imageDir) ? 'YES' : 'NO'));
                
                $files = scandir($imageDir);
                if ($files !== false) {
                    error_log("Files in directory:");
                    foreach ($files as $file) {
                        if ($file !== '.' && $file !== '..') {
                            $filePath = $imageDir . $file;
                            $fileSize = file_exists($filePath) ? filesize($filePath) : 'N/A';
                            error_log("  - " . $file . " (Size: " . $fileSize . " bytes)");
                        }
                    }
                } else {
                    error_log("Could not scan directory");
                }
            }
            error_log("=== DIRECTORY LISTING END ===");
        }

        // Process each voucher (each voucher gets its own code, even if same type)
        foreach ($redeemedVouchers as $index => $voucher) {
            // Generate quantity number of vouchers for this voucher type
            for ($i = 0; $i < $voucher['quantity']; $i++) {
                // Add page break if not the first voucher
                if ($index > 0 || $i > 0) {
                    $pdf->AddPage();
                }

                // Generate unique voucher code for each individual voucher
                $uniqueVoucherCode = '';
                do {
                    $uniqueVoucherCode = 'VCH' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
                    // In a real scenario, you'd check database for uniqueness
                    // For now, we assume it's unique enough for demonstration
                    $voucherCodeExists = false; // Placeholder for uniqueness check
                    // In a real application, you would query the database here:
                    // $stmt = $pdo->prepare("SELECT 1 FROM RedeemedVoucher WHERE voucher_code = ?");
                    // $stmt->execute([$uniqueVoucherCode]);
                    // $voucherCodeExists = $stmt->fetchColumn();
                } while ($voucherCodeExists);

                $redemptionDate = date('Y-m-d H:i:s');
                $userId = $_SESSION['Id'] ?? null;

                if ($userId === null) {
                    error_log("User ID not found in session for PDF generation.");
                    // Handle error, e.g., skip this voucher or throw an exception
                    continue; 
                }

                // Insert into RedeemedVoucher table
                $insertStmt = $pdo->prepare("INSERT INTO RedeemedVoucher (voucher_code, voucher_id, user_id, redemption_date, status) VALUES (?, ?, ?, ?, ?)");
                $insertStmt->execute([
                    $uniqueVoucherCode,
                    $voucher['voucher_id'],
                    $userId,
                    $redemptionDate,
                    'redeemed'
                ]);

                // Voucher title with better styling
                $pdf->SetFont('helvetica', 'B', 16);
                $pdf->SetTextColor(0, 102, 204); // Blue color
                $pdf->Cell(0, 10, strtoupper($voucher['title']), 0, 1, 'C');
                $pdf->SetTextColor(0, 0, 0); // Reset to black
                $pdf->Ln(5);

                // Prominent voucher code display
                $pdf->SetFont('helvetica', 'B', 14);
                $pdf->SetFillColor(240, 240, 240); // Light gray background
                $pdf->Cell(0, 10, 'VOUCHER CODE: ' . $uniqueVoucherCode, 1, 1, 'C', true);
                $pdf->Ln(5);

                // Debug directory contents (run once per PDF generation)
                static $debugRun = false;
                if (!$debugRun) {
                    debugListImageDirectory(dirname(dirname(__FILE__)));
                    $debugRun = true;
                }

                // Add voucher image with enhanced debugging
                $voucherId = $voucher['voucher_id'] ?? null;
                $existingImagePath = $voucher['image'] ?? null;
                
                // TEMPORARY: Extra logging for problematic vouchers
                if (stripos($voucher['title'], 'fast food') !== false || stripos($voucher['title'], 'hotel') !== false) {
                    error_log("=== PROBLEMATIC VOUCHER DEBUG ===");
                    error_log("Title: " . $voucher['title']);
                    error_log("Voucher ID: " . ($voucherId ?? 'NULL'));
                    error_log("Existing Image Path: " . ($existingImagePath ?? 'NULL'));
                    error_log("Full voucher data: " . print_r($voucher, true));
                }
                
                $imagePaths = getVoucherImagePath($pdo, $voucherId, $voucher['title'], $existingImagePath);
                
                // Use enhanced image loading function
                $imageLoaded = loadVoucherImage($pdf, $imagePaths, $voucher['title']);

                // Voucher details table
                $pdf->SetFont('helvetica', '', 10);
                
                // Create a styled table for voucher details
                $html = '<table border="1" cellpadding="8" style="border-collapse: collapse; width: 100%;">
                    <tr style="background-color: #e8f4f8;">
                        <td width="25%" style="font-weight: bold; background-color: #d1e7dd;">Description</td>
                        <td width="75%">' . htmlspecialchars($voucher['description'] ?? 'No description available') . '</td>
                    </tr>
                    <tr style="background-color: #e8f4f8;">
                        <td style="font-weight: bold; background-color: #d1e7dd;">Points Cost (Each)</td>
                        <td>' . ($voucher['points_cost'] / $voucher['quantity']) . ' points</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; background-color: #d1e7dd;">Redemption Date</td>
                        <td>' . $redemptionDate . '</td>
                    </tr>
                </table>';

                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Ln(5); // Reduced spacing

                // Generate and add QR Code - COMPACT VERSION
                try {
                    // Add QR code section header
                    $pdf->SetFont('helvetica', 'B', 9);
                    $pdf->Cell(0, 4, 'VOUCHER CODE:', 0, 1, 'C');
                    $pdf->Ln(2);
                    
                    // Calculate position to center the QR code
                    $qrSize = 25; // Smaller QR code to save space
                    $pageWidth = $pdf->getPageWidth();
                    $qrX = ($pageWidth - $qrSize) / 2;
                    
                    // Use TCPDF's built-in QR code generation
                    $pdf->write2DBarcode($uniqueVoucherCode, 'QRCODE,L', $qrX, '', $qrSize, $qrSize, array(
                        'border' => 0,
                        'vpadding' => 0,
                        'hpadding' => 0,
                        'fgcolor' => array(0,0,0),
                        'bgcolor' => array(255,255,255),
                        'module_width' => 1,
                        'module_height' => 1
                    ), 'N');
                    
                    $pdf->Ln($qrSize + 2);
                    
                    
                } catch (Exception $qrException) {
                    error_log("Error in QR code section: " . $qrException->getMessage());
                    // Add compact placeholder text if QR generation failed
                    $pdf->SetFont('helvetica', 'I', 7);
                    $pdf->Cell(0, 3, '[QR Code unavailable - Code: ' . $uniqueVoucherCode . ']', 0, 1, 'C');
                    $pdf->Ln(3);
                }

                // Terms and conditions section - COMPACT VERSION
                $voucherTerms = getVoucherTermsAndConditions($pdo, $voucherId);
                
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->Cell(0, 4, 'TERMS AND CONDITIONS:', 0, 1, 'L');
                $pdf->SetFont('helvetica', '', 7);
                
                // Limit terms text to prevent overflow
                $maxTermsLength = 300; // Limit characters to ensure it fits
                $displayTerms = strlen($voucherTerms) > $maxTermsLength ? 
                    substr($voucherTerms, 0, $maxTermsLength) . '...' : $voucherTerms;
                
                $pdf->MultiCell(0, 3, $displayTerms, 0, 'L');
            }
        }

        // Clear any output buffer before sending PDF
        if (ob_get_length()) {
            ob_clean();
        }
        
        // Output PDF as download
        $filename = 'vouchers_redeemed_' . date('YmdHis') . '.pdf';
        
        // Set headers for file download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        // Output PDF
        $pdf->Output($filename, 'D');
        exit;

    } catch (Exception $e) {
        // Clear any output buffer
        if (ob_get_length()) {
            ob_clean();
        }
        
        // Reset headers in case of error
        header('Content-Type: application/json');
        $response['message'] = 'Error generating PDF: ' . $e->getMessage();
        error_log('PDF Generation Error: ' . $e->getMessage());
        echo json_encode($response);
    }
} else {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
}
?>