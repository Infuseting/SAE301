import React from "react";
import Header from "@/Components/Header";
import Footer from "@/Components/Footer";

import RaceCard from "@/Pages/Admin/RaceCard";
export default function RaceManagement({ races }) {
    return (
        <div className=" min-h-screen">
            <Header />
            <div className="container mx-auto px-4 py-8">
                <h1 className="text-2xl font-bold mb-4">Gestion des Courses</h1>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {races.map((race) => (
                        <RaceCard key={race.race_id} race={race} />
                    ))}
                </div>
            </div>
            <Footer />
        </div>
    );
}
