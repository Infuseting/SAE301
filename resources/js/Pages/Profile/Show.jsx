import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage, useForm } from '@inertiajs/react';
import UserAvatar from '@/Components/UserAvatar';
import UserLastRaces from '@/Components/UserLastRaces';


export default function Show({ user, isOwner }) {
    const messages = usePage().props.translations?.messages || {};
    const { post } = useForm();

    const calculateAge = (birthDate) => {
        if (!birthDate) return null;
        const today = new Date();
        const birth = new Date(birthDate);
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        return age;
    };

    if (!user.is_public && !isOwner) {
        return (
            <AuthenticatedLayout>
                <Head title={messages.private_profile || 'Profil Privé'} />
                <div className="py-12">
                    <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                        <div className="bg-white p-4 shadow-sm border border-gray-200 sm:rounded-lg sm:p-8 text-center">
                            <UserAvatar
                                user={user}
                                className="h-20 w-20 mx-auto mb-4 opacity-50 text-2xl"
                            />
                            <h3 className="text-lg font-medium text-gray-900">{user.name}</h3>
                            <p className="mt-2 text-gray-600">{messages.profile_is_private || 'Ce profil est privé.'}</p>
                        </div>
                    </div>
                </div>

            </AuthenticatedLayout>
        );
    }

    const formattedDate = new Date(user.created_at).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });

    return (
        <AuthenticatedLayout>
            <Head title={(messages.profile_of || 'Profil de :name').replace(':name', user.name)} />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm border border-gray-200 sm:rounded-lg">
                        <div className="flex flex-col items-center sm:flex-row sm:items-start sm:space-x-8">
                            <div className="flex-shrink-0">
                                <UserAvatar
                                    user={user}
                                    className="h-32 w-32 border-4 border-gray-100 text-5xl"
                                />
                            </div>
                            <div className="mt-4 sm:mt-0 flex-1 text-center sm:text-left">
                                <div className="flex flex-col sm:flex-row sm:justify-between sm:items-start">
                                    <div className="flex flex-col gap-3 justify-center sm:justify-start">
                                        <div className="flex items-baseline space-x-2 justify-center sm:justify-start">
                                            {user.is_public ? (
                                                <svg width="1.1em" height="1em" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns" className="inline align-baseline text-gray-900">
    
                                                    <title>globe</title>
                                                    <desc>Created with Sketch Beta.</desc>
                                                    <defs>

                                                </defs>
                                                    <g id="Page-1" stroke="none" strokeWidth="1" fill="none" fillRule="evenodd" sketch:type="MSPage">
                                                        <g id="Icon-Set" sketch:type="MSLayerGroup" transform="translate(-204.000000, -671.000000)" fill="currentColor">
                                                            <path d="M231.596,694.829 C229.681,694.192 227.622,693.716 225.455,693.408 C225.75,691.675 225.907,689.859 225.957,688 L233.962,688 C233.783,690.521 232.936,692.854 231.596,694.829 L231.596,694.829 Z M223.434,700.559 C224.1,698.95 224.645,697.211 225.064,695.379 C226.862,695.645 228.586,696.038 230.219,696.554 C228.415,698.477 226.073,699.892 223.434,700.559 L223.434,700.559 Z M220.971,700.951 C220.649,700.974 220.328,701 220,701 C219.672,701 219.352,700.974 219.029,700.951 C218.178,699.179 217.489,697.207 216.979,695.114 C217.973,695.027 218.98,694.976 220,694.976 C221.02,694.976 222.027,695.027 223.022,695.114 C222.511,697.207 221.822,699.179 220.971,700.951 L220.971,700.951 Z M209.781,696.554 C211.414,696.038 213.138,695.645 214.936,695.379 C215.355,697.211 215.9,698.95 216.566,700.559 C213.927,699.892 211.586,698.477 209.781,696.554 L209.781,696.554 Z M208.404,694.829 C207.064,692.854 206.217,690.521 206.038,688 L214.043,688 C214.093,689.859 214.25,691.675 214.545,693.408 C212.378,693.716 210.319,694.192 208.404,694.829 L208.404,694.829 Z M208.404,679.171 C210.319,679.808 212.378,680.285 214.545,680.592 C214.25,682.325 214.093,684.141 214.043,686 L206.038,686 C206.217,683.479 207.064,681.146 208.404,679.171 L208.404,679.171 Z M216.566,673.441 C215.9,675.05 215.355,676.789 214.936,678.621 C213.138,678.356 211.414,677.962 209.781,677.446 C211.586,675.523 213.927,674.108 216.566,673.441 L216.566,673.441 Z M219.029,673.049 C219.352,673.027 219.672,673 220,673 C220.328,673 220.649,673.027 220.971,673.049 C221.822,674.821 222.511,676.794 223.022,678.886 C222.027,678.973 221.02,679.024 220,679.024 C218.98,679.024 217.973,678.973 216.979,678.886 C217.489,676.794 218.178,674.821 219.029,673.049 L219.029,673.049 Z M223.954,688 C223.9,689.761 223.74,691.493 223.439,693.156 C222.313,693.058 221.168,693 220,693 C218.832,693 217.687,693.058 216.562,693.156 C216.26,691.493 216.1,689.761 216.047,688 L223.954,688 L223.954,688 Z M216.047,686 C216.1,684.239 216.26,682.507 216.562,680.844 C217.687,680.942 218.832,681 220,681 C221.168,681 222.313,680.942 223.438,680.844 C223.74,682.507 223.9,684.239 223.954,686 L216.047,686 L216.047,686 Z M230.219,677.446 C228.586,677.962 226.862,678.356 225.064,678.621 C224.645,676.789 224.1,675.05 223.434,673.441 C226.073,674.108 228.415,675.523 230.219,677.446 L230.219,677.446 Z M231.596,679.171 C232.936,681.146 233.783,683.479 233.962,686 L225.957,686 C225.907,684.141 225.75,682.325 225.455,680.592 C227.622,680.285 229.681,679.808 231.596,679.171 L231.596,679.171 Z M220,671 C211.164,671 204,678.163 204,687 C204,695.837 211.164,703 220,703 C228.836,703 236,695.837 236,687 C236,678.163 228.836,671 220,671 L220,671 Z" id="globe" sketch:type="MSShapeGroup">

                                                            </path>
                                                        </g>
                                                   </g>
                                                </svg>
                                            ) : (
                                                <svg width="1.1em" height="1em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M7 10.0288C7.47142 10 8.05259 10 8.8 10H15.2C15.9474 10 16.5286 10 17 10.0288M7 10.0288C6.41168 10.0647 5.99429 10.1455 5.63803 10.327C5.07354 10.6146 4.6146 11.0735 4.32698 11.638C4 12.2798 4 13.1198 4 14.8V16.2C4 17.8802 4 18.7202 4.32698 19.362C4.6146 19.9265 5.07354 20.3854 5.63803 20.673C6.27976 21 7.11984 21 8.8 21H15.2C16.8802 21 17.7202 21 18.362 20.673C18.9265 20.3854 19.3854 19.9265 19.673 19.362C20 18.7202 20 17.8802 20 16.2V14.8C20 13.1198 20 12.2798 19.673 11.638C19.3854 11.0735 18.9265 10.6146 18.362 10.327C18.0057 10.1455 17.5883 10.0647 17 10.0288M7 10.0288V8C7 5.23858 9.23858 3 12 3C14.7614 3 17 5.23858 17 8V10.0288" stroke="#000000" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                                                </svg>
                                            )}
                                            <h3 className="text-2xl font-bold text-gray-900">{user.name}</h3>
                                            <p className="">-</p>
                                            <p className="text-l text-gray-500">{(messages.member_since || 'Membre depuis le :date').replace(':date', formattedDate)}</p>
                                        </div>
                                        <div className="flex items-baseline space-x-2 justify-center sm:justify-start">
                                            <p className="text-s text-gray-500">{"Age : " + calculateAge(user.birth_date)} {messages.years_old || 'ans'}</p>
                                        </div>
                                    </div>
                                    {usePage().props.auth.user && usePage().props.auth.user.id === user.id && (
                                        <div className="flex gap-2 mt-4 sm:mt-0 flex-col sm:flex-row">
                                            <Link
                                                href={route('profile.edit')}
                                                className="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4 mr-2">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                </svg>
                                                {messages.edit_profile || 'Modifier'}
                                            </Link>
                                            <button
                                                onClick={() => post(route('logout'))}
                                                className="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4 mr-2">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h5.25A2.25 2.25 0 0 1 18 5.25v13.5A2.25 2.25 0 0 1 15.75 21H10.5a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3h12.75" />
                                                </svg>
                                                {messages.logout || 'Log out'}
                                            </button>
                                        </div>
                                    )}
                                </div>
                                <p>{user.is_public}</p>
                                {user.description && (
                                    <div className="mt-4 prose prose-sm max-w-none text-gray-600">
                                        <p>{user.description}</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                    {isOwner && (user.license_number || user.address || user.phone) && (
                        <div className="mt-4 bg-gray-50 border border-gray-200 rounded-lg p-4 flex flex-wrap gap-6 items-center justify-center sm:justify-start">
                            <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4.4955 7.44088C3.54724 8.11787 2.77843 8.84176 2.1893 9.47978C0.857392 10.9222 0.857393 13.0778 2.1893 14.5202C3.9167 16.391 7.18879 19 12 19C13.2958 19 14.4799 18.8108 15.5523 18.4977L13.8895 16.8349C13.2936 16.9409 12.6638 17 12 17C7.9669 17 5.18832 14.82 3.65868 13.1634C3.03426 12.4872 3.03426 11.5128 3.65868 10.8366C4.23754 10.2097 4.99526 9.50784 5.93214 8.87753L4.4955 7.44088Z" fill="#0F0F0F"/>
                                <path d="M8.53299 11.4784C8.50756 11.6486 8.49439 11.8227 8.49439 12C8.49439 13.933 10.0614 15.5 11.9944 15.5C12.1716 15.5 12.3458 15.4868 12.516 15.4614L8.53299 11.4784Z" fill="#0F0F0F"/>
                                <path d="M15.4661 12.4471L11.5473 8.52829C11.6937 8.50962 11.8429 8.5 11.9944 8.5C13.9274 8.5 15.4944 10.067 15.4944 12C15.4944 12.1515 15.4848 12.3007 15.4661 12.4471Z" fill="#0F0F0F"/>
                                <path d="M18.1118 15.0928C19.0284 14.4702 19.7715 13.7805 20.3413 13.1634C20.9657 12.4872 20.9657 11.5128 20.3413 10.8366C18.8117 9.18002 16.0331 7 12 7C11.3594 7 10.7505 7.05499 10.1732 7.15415L8.50483 5.48582C9.5621 5.1826 10.7272 5 12 5C16.8112 5 20.0833 7.60905 21.8107 9.47978C23.1426 10.9222 23.1426 13.0778 21.8107 14.5202C21.2305 15.1486 20.476 15.8603 19.5474 16.5284L18.1118 15.0928Z" fill="#0F0F0F"/>
                                <path d="M2.00789 3.42207C1.61736 3.03155 1.61736 2.39838 2.00789 2.00786C2.39841 1.61733 3.03158 1.61733 3.4221 2.00786L22.0004 20.5862C22.391 20.9767 22.391 21.6099 22.0004 22.0004C21.6099 22.3909 20.9767 22.3909 20.5862 22.0004L2.00789 3.42207Z" fill="#0F0F0F"/>
                            </svg>
                            {user.license_number && (
                                <div className="flex items-center gap-2">
                                    <span className="text-sm text-gray-700 font-medium">Licence:</span>
                                    <span className="text-sm text-gray-600">{user.license_number}</span>
                                </div>
                            )}
                            {user.address && (
                                <div className="flex items-center gap-2">
                                    <span className="text-sm text-gray-700 font-medium">Adresse:</span>
                                    <span className="text-sm text-gray-600">{user.address}</span>
                                </div>
                            )}
                            {user.phone && (
                                <div className="flex items-center gap-2">
                                    <span className="text-sm text-gray-700 font-medium">Téléphone:</span>
                                    <span className="text-sm text-gray-600">{user.phone}</span>
                                </div>
                            )}
                        </div>
                    )}                </div>
            </div>
            <UserLastRaces />
        </AuthenticatedLayout >
    );
}