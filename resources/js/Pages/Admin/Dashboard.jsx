import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard({ stats }) {
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
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
