const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://turismobk.intelectalab.com/api/v1';

export async function fetchApi(endpoint: string, options: RequestInit = {}) {
  const token = typeof window !== 'undefined' ? localStorage.getItem('token') : null;
  const tenantSlug = process.env.NEXT_PUBLIC_TENANT_SLUG || 'demo-travel';

  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Tenant-Slug': tenantSlug,
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...options.headers,
  };

  const response = await fetch(`${API_URL}${endpoint}`, {
    ...options,
    headers,
  });

  if (!response.ok) {
    const errorData = await response.json().catch(() => ({}));
    throw new Error(errorData.message || 'Dogoggorri qunnamtii uumameera.');
  }

  return response.json();
}