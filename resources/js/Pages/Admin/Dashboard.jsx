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
          <div className="bg-white  p-6 shadow sm:rounded-lg">
            <h2 className="text-lg font-semibold text-gray-800 ">Logs</h2>
            <p className="text-3xl font-bold text-gray-900 ">{stats.logs}</p>
          </div>
          <div className="bg-white  p-6 shadow sm:rounded-lg flex flex-col justify-between">
            <div>
              <h2 className="text-lg font-semibold text-gray-800 ">Utilisateurs</h2>
              <p className="text-sm text-gray-500 ">Gérer les comptes utilisateurs</p>
            </div>
            <div className="mt-4">
              <Link href={route('admin.users.index')} className="inline-block rounded-md bg-gray-800 text-white px-3 py-2 text-sm">Gérer</Link>
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
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
