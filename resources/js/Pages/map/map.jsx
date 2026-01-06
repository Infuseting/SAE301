import Footer from "@/Components/Footer";

export default function Map() {
    return (
        <div className="min-h-screen flex flex-col">
            <header className="bg-emerald-600 text-white p-6">
                <h1 className="text-3xl font-bold">Carte Interactive</h1>
            </header>
            <main className="flex-grow p-6">
                <div className="w-full h-96 bg-gray-200 flex items-center justify-center">
                    <p className="text-gray-500">[Carte interactive ici]</p>
                </div>
            </main>
            <Footer />
        </div>
    );
}
