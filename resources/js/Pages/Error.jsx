import { Head } from '@inertiajs/react';

export default function Error({ status, message }) {
    const title = {
        503: '503: Service Unavailable',
        500: '500: Server Error',
        404: '404: Page Not Found',
        403: '403: Forbidden',
        401: '401: Unauthorized',
    }[status];

    const description = {
        503: 'Sorry, we are doing some maintenance. Please check back soon.',
        500: 'Whoops, something went wrong on our servers.',
        404: 'Sorry, the page you are looking for could not be found.',
        403: 'Sorry, you are forbidden from accessing this page.',
        401: 'Sorry, you are not authorized to access this page.',
    }[status] || message;

    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-gray-100  text-gray-800 ">
            <Head title={title} />
            <div className="flex flex-col items-center space-y-4 text-center sm:flex-row sm:space-x-8 sm:space-y-0 sm:text-left">
                <div className="text-6xl font-bold text-gray-400 ">{status}</div>
                <div className="border-l-2 border-gray-400  pl-8">
                    <h1 className="text-3xl font-bold text-gray-900 ">{title}</h1>
                    <p className="mt-2 text-lg text-gray-500 ">{description}</p>
                    <a href="/" className="mt-6 inline-block rounded bg-[#9333ea] px-5 py-2 text-sm font-medium text-white transition hover:bg-[#7a2ce6] focus:outline-none focus:ring focus:ring-[#9333ea]/40">
                        Go Home
                    </a>
                </div>
            </div>
        </div>
    );
}
