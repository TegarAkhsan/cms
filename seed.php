<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=cms_db;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "INSERT IGNORE INTO containers (id, booking_no, vessel, voyage, type, weight, commodity, origin, destination, eta, status, owner_id, operator_id, position_lat, position_lng, position_desc, created_at) VALUES
    ('CTR007','BK-2026-0320','KM. Garuda Mas','GM-2026-04','20ft Dry',16000,'Furnitur','Jepara','Surabaya','2026-03-08','gate_in',4,2,-7.2580,112.7530,'Yard B-01, Tanjung Perak','2026-03-05 08:00:00'),
    ('CTR008','BK-2026-0321','MV. Samudra Biru','SB-2026-02','40ft Dry',26000,'Elektronik','Batam','Jakarta','2026-03-09','booking',5,3,-6.1055,106.8310,'Pending Depo Placement','2026-03-06 09:15:00'),
    ('CTR009','BK-2026-0322','MV. Cemara Indah','CI-2026-02','20ft Reefer',14000,'Ikan Laut','Makassar','Surabaya','2026-03-10','on_vessel',4,2,-6.5000,115.0000,'On Board MV. Cemara Indah','2026-03-05 10:20:00'),
    ('CTR010','BK-2026-0323','KM. Nusantara Jaya','NJ-2026-03','40ft HC',22000,'Plastik','Surabaya','Makassar','2026-03-11','completed',5,2,-5.1476,119.4327,'Depo Pelindo Makassar','2026-03-01 11:30:00'),
    ('CTR011','BK-2026-0324','MV. Samudra Biru','SB-2026-02','20ft Dry',17000,'Ban Mobil','Jakarta','Papua','2026-03-15','clearance',4,2,-6.1000,106.8300,'Bea Cukai Priok','2026-03-07 14:00:00'),
    ('CTR012','BK-2026-0325','KM. Garuda Mas','GM-2026-05','20ft Dry',19000,'Besi Baja','Surabaya','Batam','2026-03-16','discharged',5,2,-7.2650,112.7580,'Terminal TPS','2026-03-08 14:00:00'),
    ('CTR013','BK-2026-0326','MV. Samudra Biru','SB-2026-03','40ft HC',25000,'Kertas','Surabaya','Makassar','2026-03-20','booking',4,2,-7.2575,112.7521,'Menunggu Kedatangan','2026-03-09 10:00:00');";

    $pdo->exec($sql);
    echo "Dummy data seeded successfully.\n";
} catch (PDOException $e) {
    echo "Seeding failed (perhaps database doesn't exist yet): " . $e->getMessage() . "\n";
}
?>
