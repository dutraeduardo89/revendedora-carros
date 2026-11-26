import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { SettingsComponent } from './settings';
import { ApiService } from '../services/api';

describe('SettingsComponent', () => {
  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [SettingsComponent],
      providers: [
        provideRouter([]),
        { provide: ApiService, useValue: { getRegras: () => of({ novo_km_max: 0, seminovo_km_max: 30000, velho_idade_min: 15 }) } },
      ],
    }).compileComponents();
  });

  it('não envia senha curta', () => {
    const fixture = TestBed.createComponent(SettingsComponent);
    const componente = fixture.componentInstance;
    componente.senhaAtual = 'atual1';
    componente.novaSenha = '123';
    componente.confirmarSenha = '123';
    componente.salvarSenha();
    expect(componente.errosSenha['nova_senha']).toBeTruthy();
  });
});
