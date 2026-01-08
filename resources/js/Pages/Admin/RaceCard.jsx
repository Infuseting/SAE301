import React from "react";
import { Link, usePage } from "@inertiajs/react";

export default function RaceCard({ race }) {
    return (
        <div key={race.race_id} className="border rounded-md p-4 w-[350px]">
            <h2 className="text-xl font-semibold">{race.race_name}</h2>
            <div className="h-48 border mb-2 flex items-center bg-gray-200 justify-center overflow-hidden rounded-xl mt-1">
                {race.image_url && (
                    <img
                        src={
                            race.image_url.startsWith("/storage/")
                                ? race.image_url
                                : `/storage/${race.image_url}`
                        }
                        alt={race.race_name}
                        className="w-full h-48 object-cover rounded mb-2"
                    />
                )}
            </div>
            <p> Description : {race.race_description}</p>
            <div className="flex flex-col">
                <Link
                    href={route("admin.races.edit", { id: race.race_id })}
                    className="mt-2 w-1/2 inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                    Éditer la course
                </Link>
                <Link
                    href={route("admin.races.index")}
                    className="mt-2 w-auto inline-block px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                >
                    Gérer les inscriptions
                </Link>
            </div>
        </div>
    );
}
