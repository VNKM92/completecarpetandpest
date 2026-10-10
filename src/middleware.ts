import { NextRequest, NextResponse } from 'next/server';
import { jwtVerify } from 'jose';

const JWT_SECRET = process.env.JWT_SECRET || 'brisbane-carpet-pest-experts-secret-key-2026-super-secure';
const encodedSecret = new TextEncoder().encode(JWT_SECRET);

export const config = {
  matcher: [
    /*
     * Match all requests except Next.js internals, static files, and assets
     */
    '/((?!_next|_not-found|favicon.ico|images|assets|icons|font).*)',
  ],
};

export async function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // Handle CORS OPTIONS preflight requests for external websites calling APIs
  if (request.method === 'OPTIONS') {
    return new NextResponse(null, {
      status: 200,
      headers: {
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
        'Access-Control-Allow-Headers': 'Content-Type, Authorization, X-Requested-With, X-CSRF-Token',
        'Access-Control-Max-Age': '86400',
      },
    });
  }

  let token =
    request.cookies.get('admin_token')?.value ||
    request.cookies.get('customer_token')?.value ||
    request.cookies.get('auth_token')?.value;

  const authHeader = request.headers.get('authorization');
  if (!token && authHeader && authHeader.startsWith('Bearer ')) {
    token = authHeader.substring(7);
  }

  let isAuthenticated = false;
  let userRoleSlug = 'customer';

  if (token) {
    try {
      const { payload } = await jwtVerify(token, encodedSecret);
      if (payload && payload.userId) {
        isAuthenticated = true;
        userRoleSlug = (payload.roleSlug as string) || 'customer';
      }
    } catch (err) {
      isAuthenticated = false;
    }
  }

  // 1. If accessing old /admin/login, redirect to canonical /login
  if (pathname === '/admin/login') {
    const loginUrl = new URL('/login', request.url);
    return NextResponse.redirect(loginUrl);
  }

  // 2. Protect Admin Web Routes: /admin and /admin/*
  if (pathname.startsWith('/admin')) {
    if (!isAuthenticated) {
      const loginUrl = new URL('/login', request.url);
      loginUrl.searchParams.set('returnUrl', pathname);
      return NextResponse.redirect(loginUrl);
    }
  }

  // 3. Protect Customer Dashboard Route: /dashboard
  if (pathname.startsWith('/dashboard')) {
    if (!isAuthenticated) {
      const loginUrl = new URL('/login', request.url);
      loginUrl.searchParams.set('returnUrl', pathname);
      return NextResponse.redirect(loginUrl);
    }
  }

  // 4. Protect Employee Route: /employee
  if (pathname.startsWith('/employee')) {
    if (!isAuthenticated) {
      const loginUrl = new URL('/login', request.url);
      loginUrl.searchParams.set('returnUrl', pathname);
      return NextResponse.redirect(loginUrl);
    }
  }

  // 5. Protect Admin API Routes: /api/admin/*
  if (pathname.startsWith('/api/admin')) {
    if (!isAuthenticated) {
      return NextResponse.json(
        {
          success: false,
          message: 'Access Denied: You must be authenticated to access this protected resource.',
        },
        { status: 401 }
      );
    }
  }

  // 6. Build Response with Enterprise Security Headers
  const response = NextResponse.next();

  response.headers.set('X-Content-Type-Options', 'nosniff');
  response.headers.set('X-Frame-Options', 'SAMEORIGIN');
  response.headers.set('X-XSS-Protection', '1; mode=block');
  response.headers.set('Referrer-Policy', 'strict-origin-when-cross-origin');

  return response;
}
