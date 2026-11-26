import { TestBed } from '@angular/core/testing';
import { AuthService } from './auth.service';

describe('AuthService', () => {
  let service: AuthService;

  beforeEach(() => {
    localStorage.clear();
    TestBed.configureTestingModule({});
    service = TestBed.inject(AuthService);
  });

  it('guarda e remove a sessão', () => {
    service.login('loja', 'token-teste');
    expect(service.isLoggedIn()).toBe(true);
    expect(service.token()).toBe('token-teste');
    service.logout();
    expect(service.isLoggedIn()).toBe(false);
    expect(localStorage.getItem('usuarioLogado')).toBeNull();
  });
});
