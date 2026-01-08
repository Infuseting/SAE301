import React from "react";
import Header from "@/Components/Header";
import Footer from "@/Components/Footer";
import { Link } from "@inertiajs/react";

function MyRaidCard({ raid }) {
    return (
        <div className="bg-white rounded-lg shadow-md p-4 m-4 w-1/3 h-1/2">
            <h2 className="text-xl font-bold mb-2">{raid.name}</h2>
            <p className="text-gray-600 mb-4">{raid.description}</p>
            <Link
                href={route("raids.show", { id: raid.id })}
                className="inline-block px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700"
            >
                Voir les détails
            </Link>
        </div>
    );
}

export default function MyRaidIndex({ raids }) {
    console.log(raids);
    return (
        <div className="min-h-screen flex flex-col ">
            <Header />
            <div className=" mt-20">
                <main className="flex flex-col flex-grow p-6 items-center">
                    <div className="w-full md:w-[80%]">
                        <p className="font-bold text-3xl ml-5">
                            Historique de mes raids
                        </p>
                    </div>
                    {raids.length === 0 && (
                        <div className="w-full md:w-[80%] min-h-[600px] bg-gray-200 flex flex-col justify-center items-center rounded-xl shadow-md mt-6">
                            <p className="text-gray-600 text-xl">
                                Vous n'avez pas encore participé à des raids.
                            </p>
                        </div>
                    )}
                    {raids.length > 0 && (
                        <div className="w-full md:w-[80%]">
                            <div className="w-full md:w-[80%] min-h-[600px] bg-gray-200 flex  rounded-xl shadow-md mt-6">
                                {raids.map((raid) => (
                                    <MyRaidCard key={raid.id} raid={raid} />
                                ))}
                            </div>
                        </div>
                    )}
                </main>
                <Footer />
            </div>
        </div>
    );
}
