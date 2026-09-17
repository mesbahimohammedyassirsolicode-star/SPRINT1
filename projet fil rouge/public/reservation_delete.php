<?php
/**
 * Delete / Cancel Reservation (Delete)
 */

declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
$id_reservation = (int)($_GET['id'] ?? 0);

if ($id_reservation > 0) {
    try {
        // We delete the reservation; foreign keys with ON DELETE CASCADE will handle child records,
        // or we can remove payment/invoice constraints if required.
        $stmt = $pdo->prepare("DELETE FROM reservation WHERE id_reservation = ?");
        $stmt->execute([$id_reservation]);

        header("Location: index.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        // If deletion is restricted by existing invoices or payments, fallback to marking as 'Annulée'
        $stmt = $pdo->prepare("UPDATE reservation SET statut_reservation = 'Annulée' WHERE id_reservation = ?");
        $stmt->execute([$id_reservation]);

        header("Location: index.php?msg=deleted");
        exit;
    }
}

header("Location: index.php");
exit;
