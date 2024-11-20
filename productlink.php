<?php

/* Dit bestand linkt geschikte Woocommerce producten aan voertuigen.
Dit vindt plaats via het ACF relationship field 'compatibel' behorend bij de custom post type 'motor'. */

$csv_bestand =
    "#Hier path CSV-bestand invullen#";

$voertuig_compatibel = [];

if (($handle = fopen($csv_bestand, "r")) !== false) {
    // Header van CSV-bestand overslaan
    $header = fgetcsv($handle);

    while (($row = fgetcsv($handle)) !== false) {
        $voertuigcode = $row[0]; // Unieke voertuigcode
        $barcode = $row[2]; // Unieke productbarcode

        // Deze query zoekt het juiste product op basis van de productbarcode
        $product_post = get_posts([
            "post_type" => "product",
            "meta_key" => "barcode",
            "meta_value" => $barcode,
            "posts_per_page" => 1,
            "fields" => "ids",
        ]);

        if (empty($product_post)) {
            echo "Geen product aangetroffen met barcode {$barcode}\n";
            continue;
        }

        $geschikt_product_id = $product_post[0];

        // Deze query zoekt het juiste voertuig op basis van de voertuigcode
        $voertuig_post = get_posts([
            "post_type" => "motor",
            "meta_key" => "vehicle_code",
            "meta_value" => $voertuigcode,
            "posts_per_page" => 1,
            "fields" => "ids",
        ]);

        if (!empty($voertuig_post)) {
            $voertuig_post_id = $voertuig_post[0];

            // Maakt een array aan met geschikte producten als er nog geen array bestaat
            if (!isset($voertuig_compatibel[$voertuig_post_id])) {
                $voertuig_compatibel[$voertuig_post_id] = [];
            }

            // Voegt geschikt product toe aan de array
            $voertuig_compatibel[$voertuig_post_id][] = $geschikt_product_id;
        } else {
            echo "Geen voertuig aangetroffen met voertuigcode {$voertuigcode}\n";
        }
    }
    fclose($handle);
} else {
    echo "Fout bij openen CSV-bestand.\n";
    exit();
}

// De ACF relationship field wordt bijgewerkt voor alle voertuigen
foreach ($voertuig_compatibel as $voertuig_post_id => $compatible_products) {
    update_post_meta($voertuig_post_id, "compatibel", $compatible_products);

    acf_save_post($voertuig_post_id);

    echo "Voertuig {$voertuig_post_id} succesvol bijgewerkt.\n";
}
