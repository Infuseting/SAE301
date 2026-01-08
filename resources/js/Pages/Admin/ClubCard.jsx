import React from "react";
import { Link } from "@inertiajs/react";

/**
 * ClubCard component displays a single club card with management options.
 * Used in the admin club management page.
 *
 * @param {Object} props - Component props
 * @param {Object} props.club - Club data object
 * @returns {JSX.Element} Club card component
 */
export default function ClubCard({ club }) {
    const memberCount = club.members?.length || 0;

    return (
        <div
            key={club.club_id}
            className="border rounded-md p-4 w-[350px] bg-white shadow-sm"
        >
            <h2 className="text-xl font-semibold">{club.club_name}</h2>
            <div className="h-48 border mb-2 flex items-center bg-gray-200 justify-center overflow-hidden rounded-xl mt-1">
                {club.club_image ? (
                    <img
                        src={
                            club.club_image.startsWith("/storage/")
                                ? club.club_image
                                : `/storage/${club.club_image}`
                        }
                        alt={club.club_name}
                        className="w-full h-48 object-cover rounded mb-2"
                    />
                ) : (
                    <div className="text-gray-400 text-sm">No logo</div>
                )}
            </div>

            <div className="space-y-1 text-sm text-gray-600 mb-4">
                <p>
                    <span className="font-medium">Location:</span>{" "}
                    {club.club_city}, {club.club_postal_code}
                </p>
                <p>
                    <span className="font-medium">Members:</span> {memberCount}
                </p>
                <p>
                    <span className="font-medium">Status:</span>{" "}
                    <span
                        className={`inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${
                            club.is_approved
                                ? "bg-green-100 text-green-800"
                                : "bg-yellow-100 text-yellow-800"
                        }`}
                    >
                        {club.is_approved ? "Approved" : "Pending"}
                    </span>
                </p>
            </div>

            <div className="flex flex-col gap-2">
                <Link
                    href={route("clubs.edit", club.club_id)}
                    className="w-full inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-center"
                >
                    Edit Club
                </Link>
                <Link
                    href={route("clubs.show", club.club_id)}
                    className="w-full inline-block px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 text-center"
                >
                    View Details
                </Link>
            </div>
        </div>
    );
}
