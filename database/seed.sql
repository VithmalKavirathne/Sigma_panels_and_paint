-- Sigma Panels & Paint - Seed Data
-- Phase 1 Database.
-- Implementation for XAMPP and Hostinger Shared Hosting (MySQL/MariaDB)

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `admins`;
TRUNCATE TABLE `business_settings`;
TRUNCATE TABLE `homepage_sections`;
TRUNCATE TABLE `about_sections`;
TRUNCATE TABLE `services`;
TRUNCATE TABLE `gallery_items`;
TRUNCATE TABLE `quote_requests`;
TRUNCATE TABLE `contact_messages`;
TRUNCATE TABLE `faqs`;
TRUNCATE TABLE `seo_pages`;

-- 1. Seed Admins Table
-- Default Username/Email: admin@sigmapanels.com.au
-- Temporary Password: SigmaAdmin2026! (Bcrypt hash is documented below)
INSERT INTO `admins` (`name`, `email`, `password_hash`) VALUES 
('Sigma Administrator', 'admin@sigmapanels.com.au', '$2y$10$GY4TirJXqo6l3sqda9l2GugXDQhaNLWmqN1KOwdsDfp3VFPlZe57y');

-- 2. Seed Business Settings Table
INSERT INTO `business_settings` (
    `business_name`, 
    `tagline`, 
    `phone`, 
    `whatsapp`, 
    `email`, 
    `address`, 
    `google_map_embed`, 
    `logo_path`, 
    `primary_color`, 
    `secondary_color`
) VALUES (
    'Sigma Panels & Paint',
    'Premium Collision Restoration & Custom Paint Engineering',
    '+61 478 453 598',
    '+61478453598',
    'info@sigmapanels.com.au',
    '8 Lombank St, Acacia Ridge QLD 4110',
    '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3536.8837314603953!2d153.0232493761726!3d-27.566113976263592!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x6b914434220b332d%3A0xe54d249f39be9b24!2s8%20Lombank%20St%2C%20Acacia%20Ridge%20QLD%204110!5e0!3m2!1sen!2sau!4v1719876543210!5m2!1sen!2sau" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>',
    '/assets/images/logo/logo.png',
    '#F6F4F1',
    '#F95C4B'
);

-- 3. Seed Homepage Sections Table
INSERT INTO `homepage_sections` (`section_key`, `title`, `subtitle`, `content`, `image_path`, `sort_order`, `is_active`) VALUES 
(
    'hero', 
    'Cinematic Vehicle Restoration', 
    'Precision panel beating & paint booth engineering in Acacia Ridge', 
    'Every car tells a story, but accidents shouldn\'t define them. At Sigma Panels & Paint, we combine state-of-the-art paint booth technology with custom metal-shaping craftsmanship to deliver flawless gloss, perfect structural alignment, and showroom finishes.', 
    '/assets/images/placeholders/hero-car.jpg', 
    1, 
    1
),
(
    'craftsmanship', 
    'The Art of Colour Matching', 
    'Spectrophotometer accuracy meets master craftsmanship', 
    'Our state-of-the-art laboratory features computerized colour formula databases and advanced spectrophotometers. We map your vehicle\'s specific clear-coat wear and original pigmentation to achieve an absolute, imperceptible blend.', 
    '/assets/images/placeholders/colour-match.jpg', 
    2, 
    1
),
(
    'insurance', 
    'Hassle-Free Insurance Repairs', 
    'We coordinate directly with all major insurance providers', 
    'From first assessment to final clean, we manage the paperwork, detailing, and quality reporting for your insurer. Get back on the road with lifetime repair guarantees.', 
    '/assets/images/placeholders/insurance.jpg', 
    3, 
    1
);

-- 4. Seed About Sections Table
INSERT INTO `about_sections` (`title`, `content`, `image_path`, `sort_order`, `is_active`) VALUES 
(
    'Our Heritage of Precision', 
    'Founded on the core principle of uncompromising quality, Sigma Panels & Paint has grown from a specialized dent repair workshop into Acacia Ridge\'s premier collision center. Our technicians hold certified vehicle repair credentials and continually train on advanced paint booth and chassis alignment technologies.', 
    '/assets/images/placeholders/heritage.jpg', 
    1, 
    1
),
(
    'The Paint Booth Standard', 
    'Our pressurized, down-draft paint booths ensure a dust-free environment for applying factory-spec polyurethane and clear-coats. Combined with specialized high-temperature baking cycles, we bake a hard, mirror-like gloss that lasts a lifetime.', 
    '/assets/images/placeholders/paint-booth.jpg', 
    2, 
    1
);

-- 5. Seed Services Table
INSERT INTO `services` (
    `title`, 
    `slug`, 
    `short_description`, 
    `full_description`, 
    `image_path`, 
    `icon`, 
    `sort_order`, 
    `is_featured`, 
    `is_active`
) VALUES 
(
    'Panel Beating', 
    'panel-beating', 
    'Restoring structural integrity and metal panels to factory shape.', 
    'Accidents can deform high-strength steel and aluminum panels. Our chassis aligners and custom metal-shaping tools restore your vehicle\'s frame and body contours back to exact factory blueprints.', 
    '/assets/images/placeholders/service-panel.jpg', 
    'panel-beating-icon', 
    1, 
    1, 
    1
),
(
    'Spray Painting & Colour Matching', 
    'spray-painting', 
    'Mirror-finish clear coats and computerized colour matching.', 
    'From spot repairs to full-body resprays, we use computer-guided spectrophotometers and down-draft booths to bake pristine, durable clear-coats that blend imperceptibly.', 
    '/assets/images/placeholders/service-paint.jpg', 
    'spray-painting-icon', 
    2, 
    1, 
    1
),
(
    'Insurance Smash Repairs', 
    'insurance-repairs', 
    'Direct coordination with major insurers for smooth claims.', 
    'We provide detailed quoting, high-resolution damage imaging, and direct invoicing for major insurance providers to minimize your downtime and ease the claim process.', 
    '/assets/images/placeholders/service-insurance.jpg', 
    'insurance-icon', 
    3, 
    1, 
    1
),
(
    'Paintless Dent Removal', 
    'paintless-dent-removal', 
    'Removing minor dents and hail damage without paint correction.', 
    'Using specialized rods and leverage tools, our technicians gently massage minor dents out from behind the panels, preserving your vehicle\'s original factory paint.', 
    '/assets/images/placeholders/service-pdr.jpg', 
    'dent-removal-icon', 
    4, 
    0, 
    1
),
(
    'Fleet & Commercial Refinishing', 
    'fleet-refinishing', 
    'Fast-turnaround collision repairs for business fleets.', 
    'We offer dedicated priority slots for corporate fleets, delivery vans, and work vehicles to reduce business disruption and restore commercial branding rapidly.', 
    '/assets/images/placeholders/service-fleet.jpg', 
    'fleet-icon', 
    5, 
    0, 
    1
),
(
    'Paint Correction & Protection', 
    'paint-protection', 
    'Removing paint defects and applying ceramic coatings.', 
    'Eliminate swirl marks, oxidation, and scratches with multi-stage machine compounding, topped with hydrophobic ceramic coatings for permanent high-gloss protection.', 
    '/assets/images/placeholders/service-protection.jpg', 
    'protection-icon', 
    6, 
    0, 
    1
);

-- 6. Seed Gallery Items Table
INSERT INTO `gallery_items` (`title`, `category`, `image_path`, `description`, `sort_order`, `is_active`) VALUES 
(
    'Mercedes-Benz AMG Full Respray', 
    'Spray Painting', 
    '/assets/images/placeholders/gallery-1.jpg', 
    'Showroom finish applied to a Mercedes AMG inside our down-draft spray booth, featuring custom clear-coat gloss sweep.', 
    1, 
    1
),
(
    'Toyota Hilux Front Collision Panel Correction', 
    'Panel Beating', 
    '/assets/images/placeholders/gallery-2.jpg', 
    'Realigned front chassis and replaced fender panels using factory mounting points and computerized colour matching.', 
    2, 
    1
),
(
    'Porsche 911 Ceramic Coating', 
    'Paint Protection', 
    '/assets/images/placeholders/gallery-3.jpg', 
    'Multi-stage paint correction followed by 9H ceramic coating application for extreme depth and gloss.', 
    3, 
    1
),
(
    'Audi A4 Hail Damage Paintless Dent Removal', 
    'Dent Removal', 
    '/assets/images/placeholders/gallery-4.jpg', 
    'Complete bonnet and roof restoration removing 45+ minor hail dents without breaking the factory clear coat.', 
    4, 
    1
);

-- 7. Seed Quote Requests Table
-- Sri Lanka-friendly customer details (Sri Lankan names, polite inquiries matching Acacia Ridge/Brisbane locations)
INSERT INTO `quote_requests` (
    `customer_name`, 
    `phone`, 
    `email`, 
    `service_interest`, 
    `project_location`, 
    `message`, 
    `status`
) VALUES 
(
    'Kasun Perera', 
    '+61 478 123 456', 
    'kasun.perera@gmail.com', 
    'spray-painting', 
    'Acacia Ridge QLD', 
    'Ayyubowan, I have a Honda Civic with some deep key scratches on the passenger side door. Looking to get it resprayed and matched perfectly. I am based near Acacia Ridge. Can you provide an estimate?', 
    'pending'
),
(
    'Dilshan Silva', 
    '+61 488 234 567', 
    'dilshan@silva.net.au', 
    'panel-beating', 
    'Sunnybank QLD', 
    'Hi Sigma team, my Lexus RX front bumper was scuffed and dented in a parking lot. Do you coordinate with Allianz insurance, or should I get a private quote? Thanks, Dilshan.', 
    'pending'
),
(
    'Amara Jayasekara', 
    '+61 499 345 678', 
    'amara@jayasekara.com.au', 
    'paintless-dent-removal', 
    'Eight Mile Plains QLD', 
    'Hello, looking for a quote to pop out three small dents on my Mitsubishi Outlander hood. The paint is not damaged. Can I bring it in on Friday morning?', 
    'reviewed'
);

-- 8. Seed Contact Messages Table
-- Sri Lanka-friendly customer details
INSERT INTO `contact_messages` (`name`, `phone`, `email`, `subject`, `message`, `status`) VALUES 
(
    'Dr. Priyantha Bandara', 
    '+61 422 987 654', 
    'p.bandara@brisbanehealth.org', 
    'Fleet repair enquiry', 
    'Good afternoon, we have a small fleet of medical courier vehicles that occasionally require quick turnaround dent removal. Do you offer corporate fleet pricing accounts? Regards.', 
    'unread'
),
(
    'Nisha Fernando', 
    '+61 411 765 432', 
    'nisha.fernando@yahoo.com', 
    'Courtesy car availability', 
    'Hi, I have booked my Mazda 3 for a full paint correction next Tuesday. I just wanted to verify if you have courtesy cars available for the day? Thank you!', 
    'read'
);

-- 9. Seed FAQs Table
INSERT INTO `faqs` (`question`, `answer`, `sort_order`, `is_active`) VALUES 
(
    'How long does a typical smash repair take?', 
    'Most minor panel beating and spray painting jobs are completed within 3 to 5 business days. Major collision restorations or special parts orders can take longer. We provide a detailed timeframe estimate with every quote.', 
    1, 
    1
),
(
    'Do you offer a warranty on paintwork?', 
    'Yes, we offer a lifetime warranty on all spray painting clear-coats and panel alignments, ensuring your peace of mind and showroom finish.', 
    2, 
    1
),
(
    'Do you work with all insurance companies?', 
    'Absolutely. We provide insurance smash repair services and coordinate directly with major insurers including Allianz, Suncorp, RACQ, NRMA, and others.', 
    3, 
    1
),
(
    'What is computerized colour matching?', 
    'We use a digital spectrophotometer tool to analyze the paint color on your actual vehicle. This accounts for paint fading over time, ensuring the newly sprayed panels blend imperceptibly with the surrounding areas.', 
    4, 
    1
);

-- 10. Seed SEO Pages Table
INSERT INTO `seo_pages` (`page_key`, `meta_title`, `meta_description`, `meta_keywords`) VALUES 
(
    'home', 
    'Sigma Panels & Paint | Premium Panel Beating & Spray Painting Acacia Ridge', 
    'Acacia Ridge\'s leading prestige panel beating, spray painting, and insurance smash repair center. Cinematic gloss finish and perfect chassis alignment guaranteed.', 
    'panel beating acacia ridge, spray painting brisbane, smash repairs qld, dent removal, colour matching'
),
(
    'about', 
    'The Craft | About Sigma Panels & Paint', 
    'Learn about the heritage, precision paint booth standards, and expert panel beaters behind Sigma Panels & Paint in Acacia Ridge.', 
    'about sigma panels, certified panel beaters brisbane, paint booth standards'
),
(
    'services', 
    'Precision Services | Sigma Panels & Paint', 
    'Discover our full range of automotive repair services: custom panel beating, spray painting, insurance smash repairs, hail damage correction, and paint protection.', 
    'smash repair services, car painting services, dent removal, panel beating'
),
(
    'gallery', 
    'Our Work Showcase | Sigma Panels & Paint', 
    'View before-and-after transformations and showroom finishes from our Acacia Ridge vehicle restoration workshop.', 
    'car gallery brisbane, automotive resprays, dent repair gallery'
),
(
    'quote', 
    'Get a Premium Repair Quote | Sigma Panels & Paint', 
    'Submit details and images of your vehicle damage online to get a fast and precise repair estimate from our Acacia Ridge technicians.', 
    'get car repair quote, online panel beating quote, spray paint cost estimation'
),
(
    'contact', 
    'Connect with the Workshop | Sigma Panels & Paint', 
    'Find our phone number, email, address, and interactive map. Visit Sigma Panels & Paint at 8 Lombank St, Acacia Ridge QLD.', 
    'contact sigma panels, acacia ridge panel beating address, phone number paint shop'
);

SET FOREIGN_KEY_CHECKS = 1;
