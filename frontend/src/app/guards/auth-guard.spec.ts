import { TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { AuthGuard } from './auth.guard';
import { AuthService } from '../services/auth.service';

describe('AuthGuard', () => {
  beforeEach(() => {
    localStorage.clear();
    TestBed.configureTestingModule({ providers: [provideRouter([])] });
  });

  it('bloqueia quem não tem sessão', () => {
    const guard = TestBed.inject(AuthGuard);
    const router = TestBed.inject(Router);
    const navegar = vi.spyOn(router, 'navigate');
    expect(guard.canActivate()).toBe(false);
    expect(navegar).toHaveBeenCalledWith(['/']);
  });

  it('libera quem está autenticado', () => {
    const auth = TestBed.inject(AuthService);
    auth.login('loja', 'token-teste');
    const guard = TestBed.inject(AuthGuard);
    expect(guard.canActivate()).toBe(true);
  });
});
