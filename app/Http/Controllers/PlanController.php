<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\PointDensifier;
use App\Models\Shapefile;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->create();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
      
        // Charge directement les relations
        $points = PointDensifier::with(['departement', 'arrondissement', 'commune'])->get();
        
        // Pour le formulaire (dropdown)
        $departements = Departement::all();   

        return Inertia::render('plan/create', [
            'point_densifiers' => $points,
            'departements' => $departements,
            "arrondissements" => [],
            "communes" => [],
            'shapefiles' => Shapefile::latest()->get(),
        ]);    
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
