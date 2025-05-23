<?php
require 'config.php';
require 'tcpdf/tcpdf.php';

if (!isset($_GET['devis_id'])) {
    die('ID du devis non spécifié');
}

// Récupération des données du devis
$stmt = $pdo->prepare("
    SELECT d.*, v.nom as vehicule_nom, k.nom as kit_nom
    FROM devis d
    LEFT JOIN vehicules v ON d.id_vehicule = v.id
    LEFT JOIN kits k ON d.id_kit = k.id
    WHERE d.id = ?
");
$stmt->execute([$_GET['devis_id']]);
$devis = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$devis) {
    die('Devis non trouvé');
}

// Création du PDF
class MYPDF extends TCPDF {
    public function Header() {
        // Fond gris clair pour l'en-tête
        $this->SetFillColor(247, 247, 247);
        $this->Rect(0, 0, $this->getPageWidth(), 50, 'F');
        
        // Logo et titre
        if (file_exists('images/logo.png')) {
            $this->Image('images/logo.png', 15, 10, 40);
            $this->SetX(60);
        } else {
            $this->SetX(15);
        }
        
        // Informations de l'entreprise
        $this->SetTextColor(51, 51, 51);
        $this->SetFont('helvetica', 'B', 20);
        $this->Cell(0, 10, 'Mon Configurateur de Véhicule', 0, 1, 'R');
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 5, 'Adresse de l\'entreprise', 0, 1, 'R');
        $this->Cell(0, 5, 'Téléphone : XX XX XX XX XX', 0, 1, 'R');
        $this->Cell(0, 5, 'Email : contact@monconfig.fr', 0, 1, 'R');
    }

    public function Footer() {
        // Fond gris clair pour le pied de page
        $this->SetFillColor(247, 247, 247);
        $this->Rect(0, $this->getPageHeight() - 25, $this->getPageWidth(), 25, 'F');
        
        $this->SetY(-20);
        $this->SetTextColor(102, 102, 102);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 5, "Mon Configurateur de Véhicule - SIRET : XXXXXXXXX - TVA : FRXXXXXXXXX", 0, 1, 'C');
        $this->Cell(0, 5, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 1, 'C');
    }
}

// Initialisation du PDF
$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Métadonnées du document
$pdf->SetCreator('Mon Configurateur');
$pdf->SetAuthor('Mon Configurateur');
$pdf->SetTitle('Devis #' . $devis['id']);

// Configuration du document
$pdf->SetMargins(15, 50, 15);
$pdf->SetAutoPageBreak(TRUE, 25);
$pdf->AddPage();

// Numéro et date du devis
$pdf->SetTextColor(51, 51, 51);
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'DEVIS N°' . str_pad($devis['id'], 6, '0', STR_PAD_LEFT), 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 5, 'Date : ' . date('d/m/Y'), 0, 1, 'L');

// Informations client
$pdf->Ln(10);
$pdf->SetFillColor(236, 240, 241);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Informations Client', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);

// Tableau des informations client
$pdf->SetFillColor(247, 247, 247);
$pdf->Cell(60, 8, 'Nom et Prénom :', 1, 0, 'L', true);
$pdf->Cell(0, 8, $devis['prenom'] . ' ' . $devis['nom'], 1, 1, 'L');
$pdf->Cell(60, 8, 'Email :', 1, 0, 'L', true);
$pdf->Cell(0, 8, $devis['email'], 1, 1, 'L');
$pdf->Cell(60, 8, 'Téléphone :', 1, 0, 'L', true);
$pdf->Cell(0, 8, $devis['telephone'], 1, 1, 'L');

if (!empty($devis['message'])) {
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, 'Message du client :', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 6, $devis['message'], 1, 'L');
}

// Configuration du véhicule
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Configuration du Véhicule', 0, 1, 'L');

// Tableau de la configuration
$pdf->SetFont('helvetica', '', 11);
$pdf->SetFillColor(247, 247, 247);
$pdf->Cell(60, 8, 'Véhicule :', 1, 0, 'L', true);
$pdf->Cell(0, 8, $devis['vehicule_nom'], 1, 1, 'L');
if ($devis['kit_nom']) {
    $pdf->Cell(60, 8, 'Kit :', 1, 0, 'L', true);
    $pdf->Cell(0, 8, $devis['kit_nom'], 1, 1, 'L');
}

// Détails de la configuration
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Configuration détaillée :', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetFillColor(252, 252, 252);
$pdf->MultiCell(0, 6, $devis['configuration'], 1, 'L', true);

// Prix
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Récapitulatif des Prix', 0, 1, 'L');

// Tableau des prix avec couleurs alternées
$pdf->SetFont('helvetica', '', 11);
$pdf->SetFillColor(247, 247, 247);
$pdf->Cell(140, 8, 'Prix HT', 1, 0, 'L', true);
$pdf->Cell(40, 8, number_format($devis['prix_ht'], 2, ',', ' ') . ' €', 1, 1, 'R', true);

$pdf->SetFillColor(252, 252, 252);
$pdf->Cell(140, 8, 'TVA (20%)', 1, 0, 'L', true);
$pdf->Cell(40, 8, number_format($devis['prix_ttc'] - $devis['prix_ht'], 2, ',', ' ') . ' €', 1, 1, 'R', true);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(236, 240, 241);
$pdf->Cell(140, 10, 'Prix TTC', 1, 0, 'L', true);
$pdf->Cell(40, 10, number_format($devis['prix_ttc'], 2, ',', ' ') . ' €', 1, 1, 'R', true);

// Conditions générales
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Conditions générales :', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(102, 102, 102);
$pdf->MultiCell(0, 5, "Ce devis est valable 30 jours à compter de sa date d'émission. Les prix indiqués sont en euros et incluent la TVA au taux en vigueur de 20%. Le délai de livraison sera confirmé lors de la commande. Un acompte de 30% sera demandé à la commande.", 0, 'L');

// Zone de signature
$pdf->Ln(15);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 20, "Signature :", 0, 1, 'L');

// Génération du PDF
$pdf->Output('Devis_' . str_pad($devis['id'], 6, '0', STR_PAD_LEFT) . '.pdf', 'I');