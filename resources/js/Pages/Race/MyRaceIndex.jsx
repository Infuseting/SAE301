import Header from "@/Components/Header";
import Footer from "@/Components/Footer";

import { Link } from "@inertiajs/react";

function MyRaceCard({ race }) {
    return (
        <div className="bg-white rounded-lg shadow-md p-4 m-4 w-1/3 h-1/2">
            <h2 className="text-xl font-bold mb-2">{race.name}</h2>
            <p className="text-gray-600 mb-4">{race.description}</p>

            <Link
                href={route("races.show", { id: race.id })}
                className="inline-block px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700"
            >
                Voir les détails
            </Link>
        </div>
    );
}

export default function MyRaceIndex({ races }) {
    console.log(races);

    return (
        <div className="min-h-screen flex flex-col ">
            <Header />
            <div className=" mt-20">
                <main className="flex flex-col flex-grow p-6 items-center">
                    <div className="w-full md:w-[80%]">
                        <p className="font-bold text-3xl ml-5">
                            Historique de mes courses
                        </p>
                    </div>
                    {races.length === 0 && (
                        <div className="w-full md:w-[80%] min-h-[600px] bg-gray-200 flex flex-col justify-center items-center rounded-xl shadow-md mt-6">
                            <p className="text-gray-600 text-xl">
                                Vous n'avez pas encore participé à des courses.
                            </p>
                        </div>
                    )}
                    {races.length > 0 && (
                        <div className="w-full md:w-[80%] min-h-[600px] bg-gray-200 flex  rounded-xl shadow-md mt-6">
                            {races.map((race) => (
                                <MyRaceCard key={race.id} race={race} />
                            ))}
                        </div>
                    )}
                </main>
                <Footer />
            </div>
        </div>
    );
}
