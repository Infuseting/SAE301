import React from "react";
import Header from "@/Components/Header";
import Footer from "@/Components/Footer";
import RaidCard from "@/Pages/Admin/RaidCard";

export default function RaidManagement({ raids }) {
    return (
        <div className=" min-h-screen">
            <Header />
            <div className="container mx-auto px-4 py-8">
                <h1 className="text-2xl font-bold mb-4">Gestion des Raids</h1>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {raids.map((raid) => (
                        <RaidCard key={raid.raid_id} raid={raid} />
                    ))}
                </div>
            </div>
            <Footer />
        </div>
    );
}
