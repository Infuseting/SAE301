export default function UserLastRaces() {
    
    return (
        <div>
            <h2 className="mx-auto max-w-7xl sm:px-6 lg:px-8 mt-6 text-2xl font-bold text-gray-900">
                Vos équipes 
            </h2>
            <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 mt-6">
                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <div className="aspect-square p-4 border border-gray-200 rounded-lg bg-white shadow-sm flex flex-row overflow-hidden">
                        <div className="w-1/2 min-w-0 overflow-hidden rounded shrink-0">
                            <h3 className="text-lg font-bold text-gray-900 truncate">Nom Equipe</h3>
                            <img 
                                src="" 
                                alt="Course" 
                                className="w-full h-full object-cover"
                            />
                        </div>
                        <div className="w-px bg-gray-200 mx-3">Membres</div>
                        <ul className="w-1/2 pl-3 flex flex-col justify-center">
                            <li className="text-sm">membre 1</li>
                            <li className="text-sm">membre 2</li>
                        </ul>
                    </div> 
                </div>
            </div>
        </div>
    ); 
}