import { Head } from '@inertiajs/react';

export default function ServerError({ status, message, file, line, trace }) {
    return (
        <div className="min-h-screen bg-gray-50 flex flex-col items-center py-12 px-4 sm:px-6 lg:px-8 font-sans text-gray-900">
            <Head title={`Error ${status}: ${message}`} />

            <div className="w-full max-w-5xl space-y-8">
                {/* Header Section */}
                <div className="text-center">
                    <h1 className="text-9xl font-extrabold text-gray-200 tracking-widest">{status}</h1>
                    <div className="mt-4 px-2 relative -top-12">
                        <div className="inline-block bg-[#9333ea] text-white px-3 py-1 rounded text-sm font-semibold tracking-wider uppercase shadow-lg">
                            Server Error
                        </div>
                        <h2 className="mt-2 text-3xl font-bold text-gray-900 sm:text-4xl tracking-tight">
                            Something went wrong
                        </h2>
                        <p className="mt-4 text-lg text-gray-600 break-words max-w-3xl mx-auto">
                            {message}
                        </p>
                    </div>
                </div>

                {/* Details Card */}
                <div className="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
                    {/* File Location */}
                    <div className="bg-gray-50 px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div className="flex items-start space-x-3">
                            <div className="flex-shrink-0">
                                <svg className="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div className="overflow-hidden">
                                <h3 className="text-sm font-medium text-gray-500 uppercase tracking-wider">Exception Location</h3>
                                <p className="font-mono text-sm text-gray-800 break-all mt-1">
                                    {file}<span className="text-[#9333ea] font-bold">:{line}</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Stack Trace */}
                    <div className="p-6">
                        <h3 className="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Stack Trace</h3>
                        <div className="bg-[#1e1e1e] rounded-lg shadow-inner overflow-hidden">
                            <div className="flex items-center justify-between px-4 py-2 bg-[#2d2d2d] border-b border-[#3d3d3d]">
                                <div className="flex space-x-2">
                                    <div className="w-3 h-3 rounded-full bg-red-500"></div>
                                    <div className="w-3 h-3 rounded-full bg-yellow-500"></div>
                                    <div className="w-3 h-3 rounded-full bg-green-500"></div>
                                </div>
                                <span className="text-xs text-gray-400 font-mono">system.log</span>
                            </div>
                            <div className="p-4 overflow-x-auto overflow-y-auto max-h-[500px] scrollbar-thin scrollbar-thumb-gray-600 scrollbar-track-transparent">
                                <pre className="font-mono text-xs leading-relaxed text-gray-300 whitespace-pre-wrap">
                                    {trace.split('\n').map((line, i) => (
                                        <div key={i} className="py-0.5 hover:bg-[#2d2d2d] px-2 -mx-2 rounded pointer-events-none">
                                            <span className="text-black select-none w-8 inline-block text-right mr-3 opacity-50">{i}</span>
                                            <span className="select-text text-black">{line}</span>
                                        </div>
                                    ))}
                                </pre>
                            </div>
                        </div>
                    </div>

                    {/* Action Bar */}
                    <div className="bg-gray-50 px-6 py-4 border-t border-gray-100 text-right">
                        <a href="/" className="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-[#9333ea] hover:bg-[#7e22ce] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#9333ea] transition-colors duration-200">
                            Go Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    );
}
