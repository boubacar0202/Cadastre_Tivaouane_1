<?php

namespace Database\Seeders;

use App\Models\Departement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        /*
        $kedougou = Departement::create(['slt_departement' => 'Kedougou']);
        $salemata = Departement::create(['slt_departement' => 'Salemata']);
        $saraya = Departement::create(['slt_departement' => 'Saraya']);

        // Kedougou arrondissements et communes
        $kedougouArr1 = $kedougou->arrondissements()->create(['slt_arrondissement' => 'Kedougou']);
        $kedougouArr1->communes()->create(['slt_commune' => 'Kedougou']);

        $kedougouArr2 = $kedougou->arrondissements()->create(['slt_arrondissement' => 'Bandafassi']);
        $kedougouArr2->communes()->createMany([
            ['name' => 'Bandafassi'],
            ['name' => 'Tombouronkoto'],
            ['name' => 'Dindifelo'],
            ['name' => 'Ninefecha'],
        ]);

        $kedougouArr3 = $kedougou->arrondissements()->create(['slt_arrondissement' => 'Fongolembi']);
        $kedougouArr3->communes()->createMany([
            ['name' => 'Dimboli'],
            ['name' => 'Fongolembi'],
        ]);

        // Salemata arrondissements et communes
        $salemataArr1 = $salemata->arrondissements()->create(['slt_arrondissement' => 'Salemata']);
        $salemataArr1->communes()->create(['slt_commune' => 'Salemata']);

        $salemataArr2 = $salemata->arrondissements()->create(['slt_arrondissement' => 'Dakateli']);
        $salemataArr2->communes()->createMany([
            ['name' => 'Dakateli'],
            ['name' => 'Koyoye'],
        ]);

        $salemataArr3 = $salemata->arrondissements()->create(['slt_arrondissement' => 'Dar Salam']);
        $salemataArr3->communes()->createMany([
            ['name' => 'Dar Salam'],
            ['name' => 'Ethiolo'],
            ['name' => 'Oubadji'],
        ]);

        // Saraya arrondissements et communes
        $sarayaArr1 = $saraya->arrondissements()->create(['slt_arrondissement' => 'Saraya']);
        $sarayaArr1->communes()->create(['slt_commune' => 'Saraya']);

        $sarayaArr2 = $saraya->arrondissements()->create(['slt_arrondissement' => 'Bembou']);
        $sarayaArr2->communes()->createMany([
            ['name' => 'Bembou'],
            ['name' => 'Medina Baffe'],
            ['name' => 'Khousanto'],
        ]);

        $sarayaArr3 = $saraya->arrondissements()->create(['slt_arrondissement' => 'Sabodala']);
        $sarayaArr3->communes()->createMany([
            ['name' => 'Sabodala'],
            ['name' => 'Missirah Sirimana'],
        ]);

        */

        // Ajoutez les autres départements, arrondissements et communes comme dans l'exemple ci-dessus.
    }
    
}

