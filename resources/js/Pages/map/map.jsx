import Footer from "@/Components/Footer";
import InteractiveMap from "./InteractiveMap";
import Header from "@/Components/Header";
import React from "react";

export default function Map({ auth }) {
    const [center, setCenter] = React.useState([46.603354, 1.888334]);

    return (
        <div className="min-h-screen flex flex-col ">
            <Header auth={auth} />
            <div className=" mt-20">
                <main className="flex flex-col flex-grow p-6 items-center">
                    <div className="w-full md:w-[80%]">
                        <p className="font-bold text-3xl ml-5">
                            Liste des raids
                        </p>
                    </div>
                    <div className="w-full md:w-[80%] min-h-[600px] bg-gray-200 flex items-center justify-center rounded-xl shadow-md mt-6">
                        <InteractiveMap
                            center={center}
                            onCenterChange={setCenter}
                        />
                    </div>
                </main>
                <Footer />
            </div>
        </div>
    );
}
