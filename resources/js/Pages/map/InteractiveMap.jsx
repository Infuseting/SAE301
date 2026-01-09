import { MapContainer, TileLayer, Marker, Popup } from "react-leaflet";
import "leaflet/dist/leaflet.css";
import L from "leaflet";
import React, { useEffect } from "react";
import { useMap } from "react-leaflet";

/**
 * Composant pour mettre à jour le centre de la carte
 * quand la prop `center` change
 */
function CenterUpdater({ center }) {
    const map = useMap();

    useEffect(() => {
        if (center) {
            map.flyTo(center, 12);
        }
    }, [center, map]);

    return null;
}

/**
 * Composant enfant pour afficher les marqueurs
 * @param {Array} raids - Liste des raids
 * @param {Object} defaultIcon - Icône personnalisée
 * @param {Function} onRaidClick - Callback quand on clique sur un marqueur
 */
function RaidMarkers({ raids, defaultIcon, onRaidClick }) {
    return (
        <>
            {raids.map((raid) => (
                <Marker
                    key={raid.id}
                    position={[raid.latitude, raid.longitude]}
                    icon={defaultIcon}
                    onClick={() => {
                        // Just call the parent callback to update the center
                        // CenterUpdater will perform the flyTo() animation
                        onRaidClick([raid.latitude, raid.longitude]);
                    }}
                >
                    <Popup>
                        <div className="w-[300px]">
                            <div className="font-bold text-xl">{raid.name}</div>
                            <div className="aspect-[4/3] overflow-hidden rounded-lg mb-2 mt-2 ">
                                <img
                                    src={raid.image}
                                    alt={raid.name}
                                    className="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                                />
                            </div>{" "}
                            <div className="text-sm mt-1">
                                {" "}
                                <span className="font-bold">Description: </span>
                                {raid.description}
                            </div>
                            <div className="text-sm mt-1">
                                <span className="font-bold">Adresse: </span>
                                {raid.raid_street}, {raid.raid_city}{" "}
                                {raid.raid_postal_code}
                            </div>
                            <div className="text-sm mt-1">
                                <span className="font-bold">Dates: </span>
                                {raid.date_start} au {raid.date_end}
                            </div>
                            <div className="text-sm mt-1">
                                <span className="font-bold">Contact: </span>
                                {raid.contact_email}
                            </div>
                            <div className="text-sm mt-1">
                                <span className="font-bold">Site: </span>
                                <a
                                    href={raid.site_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-blue-500 underline"
                                >
                                    {raid.site_url}
                                </a>
                            </div>
                            <button className="mt-4 px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700">
                                voir plus
                            </button>
                        </div>
                    </Popup>
                </Marker>
            ))}
        </>
    );
}

export default function InteractiveMap({ center, onCenterChange, raids = [] }) {
    const defaultIcon = L.icon({
        iconUrl: "https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon.png",
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowUrl:
            "https://unpkg.com/leaflet@1.7.1/dist/images/marker-shadow.png",
        shadowSize: [41, 41],
    });

    // Use the raids passed in props, otherwise use an empty array
    const raidsData = raids && raids.length > 0 ? raids : [];

    return (
        <div className="w-full h-full">
            <MapContainer
                center={center}
                zoom={6}
                style={{ height: "700px", width: "100%", borderRadius: "12px" }}
            >
                <TileLayer
                    url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                    attribution="&copy; OpenStreetMap contributors"
                />
                <CenterUpdater center={center} />
                <RaidMarkers
                    raids={raidsData}
                    defaultIcon={defaultIcon}
                    onRaidClick={onCenterChange}
                />
            </MapContainer>
        </div>
    );
}
