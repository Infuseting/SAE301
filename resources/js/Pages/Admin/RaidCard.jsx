import React from "react";
import { Link } from "@inertiajs/react";

export default function RaidCard({ raid }) {
    return (
        <div key={raid.raid_id} className="border rounded-md p-4 w-[350px]">
            <h2 className="text-xl font-semibold">{raid.raid_name}</h2>
            <div className="h-48 border mb-2 flex items-center bg-gray-200 justify-center overflow-hidden rounded-xl mt-1">
                {raid.raid_image && (
                    <img
                        src={
                            raid.raid_image.startsWith("/storage/")
                                ? raid.raid_image
                                : `/storage/${raid.raid_image}`
                        }
                        alt={raid.raid_name}
                        className="w-full h-48 object-cover rounded mb-2"
                    />
                )}
            </div>
            <p> Description : {raid.raid_description}</p>
            <div className="flex flex-col">
                <Link
                    href={route("raids.edit", raid.raid_id)}
                    className="mt-2 w-1/2 inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                    Éditer le raid
                </Link>
            </div>
        </div>
    );
}
