import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Dashboard({ stats }) {
  const { auth } = usePage().props;
  const user = auth?.user;

  // Check if user has permission to approve clubs
  const canApproveClubs = user?.permissions?.includes('accept-club') ||
    user?.roles?.some(role => role.name === 'admin' || role === 'admin');

  return (
    <AuthenticatedLayout>
      <Head title="Admin Dashboard" />

      <div className="px-4 sm:px-6 lg:px-8 py-12 space-y-6">
        {/* Statistiques globales */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div className="bg-white border border-gray-200 p-6 shadow sm:rounded-lg">
            <div className="flex items-center justify-between">
              <div>
                <h2 className="text-lg font-semibold text-gray-800">Utilisateurs</h2>
                <p className="text-3xl font-bold text-gray-900 mt-2">{stats.users}</p>
              </div>
              <svg className="w-12 h-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
            <div className="mt-4">
              <Link href={route('admin.users.index')} className="inline-flex items-center text-sm font-medium text-gray-700 hover:text-gray-900">
                Gérer les utilisateurs
                <svg className="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                </svg>
              </Link>
            </div>
          </div>

          <div className="bg-white border border-gray-200 p-6 shadow sm:rounded-lg">
            <div className="flex items-center justify-between">
              <div>
                <h2 className="text-lg font-semibold text-gray-800">Logs</h2>
                <p className="text-3xl font-bold text-gray-900 mt-2">{stats.logs}</p>
              </div>
              <svg className="w-12 h-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div className="mt-4">
              <Link href={route('admin.logs.index')} className="inline-flex items-center text-sm font-medium text-gray-700 hover:text-gray-900">
                Voir les logs
                <svg className="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                </svg>
              </Link>
            </div>
          </div>

          {/* Club Approval Card - Only show if user has permission */}
          {canApproveClubs && (
            <div className="bg-white p-6 shadow sm:rounded-lg flex flex-col justify-between">
              <div>
                <h2 className="text-lg font-semibold text-gray-800">Validation des Clubs</h2>
                <p className="text-sm text-gray-500">Approuver ou rejeter les clubs en attente</p>
                {stats.pendingClubs > 0 && (
                  <div className="mt-2">
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                      {stats.pendingClubs} en attente
                    </span>
                  </div>
                )}
              </div>
              <div className="mt-4">
                <Link
                  href={route('admin.clubs.pending')}
                  className="inline-block rounded-md bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 text-sm transition"
                >
                  Gérer les clubs
                </Link>
              </div>
            </div>
          )}

          {/* Leaderboard Management Card */}
          <div className="bg-white border border-gray-200 p-6 shadow sm:rounded-lg">
            <div className="flex items-center justify-between">
              <div>
                <h2 className="text-lg font-semibold text-gray-800">Leaderboard</h2>
                <p className="text-sm text-gray-500 mt-1">Importer et gérer les résultats des courses</p>
              </div>
              <svg className="w-12 h-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <div className="mt-4">
              <Link href={route('admin.leaderboard.index')} className="inline-flex items-center text-sm font-medium text-gray-700 hover:text-gray-900">
                Gérer le leaderboard
                <svg className="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                </svg>
              </Link>
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
